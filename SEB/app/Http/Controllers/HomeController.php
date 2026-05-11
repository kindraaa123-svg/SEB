<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const CODE_EXPIRY_MINUTES = 10;
    private const FIXED_TIMEZONE_OFFSET_MINUTES = -420; // UTC+07:00 (WIB)

    public function index(Request $request): View
    {
        return $this->buildPageView($request, 'dashboard');
    }

    public function loginCode(Request $request): View
    {
        return $this->buildPageView($request, 'login');
    }

    public function unlockCode(Request $request): View
    {
        return $this->buildPageView($request, 'unlock');
    }

    public function exitCode(Request $request): View
    {
        return $this->buildPageView($request, 'exit');
    }

    public function activity(Request $request): View
    {
        return $this->buildPageView($request, 'activity');
    }

    public function account(Request $request): View
    {
        return $this->buildPageView($request, 'account');
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $supervisorId = (int) data_get($request->session()->get('supervisor'), 'id', 0);

        if ($supervisorId <= 0) {
            return redirect()
                ->route('login')
                ->withErrors(['username' => 'Session expired. Please sign in again.']);
        }

        $emailColumn = $this->firstExistingSupervisorColumn(['email', 'mail']);
        $nameColumn = $this->firstExistingSupervisorColumn(['name', 'nama', 'full_name', 'fullname']);

        $payload = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:100'],
        ]);

        $name = trim((string) ($payload['name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($emailColumn !== null && $email !== '') {
            $duplicateEmail = DB::table('supervisor')
                ->where($emailColumn, $email)
                ->where(function ($query) use ($supervisorId): void {
                    if (Schema::hasColumn('supervisor', 'supervisorid')) {
                        $query->where('supervisorid', '!=', $supervisorId);
                    } elseif (Schema::hasColumn('supervisor', 'id')) {
                        $query->where('id', '!=', $supervisorId);
                    } else {
                        $query->whereRaw('1 = 1');
                    }
                })
                ->exists();

            if ($duplicateEmail) {
                return redirect()
                    ->route('account')
                    ->withErrors(['email' => 'Email sudah digunakan oleh akun lain.'])
                    ->withInput();
            }
        }

        $updates = [];
        if ($nameColumn !== null) {
            $updates[$nameColumn] = $name === '' ? null : $name;
        }

        if ($emailColumn !== null) {
            $updates[$emailColumn] = $email === '' ? null : $email;
        }

        if (Schema::hasColumn('supervisor', 'updated_at')) {
            $updates['updated_at'] = CarbonImmutable::now();
        }

        $this->supervisorQueryById($supervisorId)->update($updates);

        return redirect()
            ->route('account')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $supervisorId = (int) data_get($request->session()->get('supervisor'), 'id', 0);

        if ($supervisorId <= 0) {
            return redirect()
                ->route('login')
                ->withErrors(['username' => 'Session expired. Please sign in again.']);
        }

        $payload = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $supervisor = $this->supervisorQueryById($supervisorId)->first();
        $storedPasswordHash = (string) data_get($supervisor, 'password', '');

        if ($storedPasswordHash === '' || !Hash::check((string) $payload['current_password'], $storedPasswordHash)) {
            return redirect()
                ->route('account')
                ->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $updates = [
            'password' => Hash::make((string) $payload['password']),
        ];

        if (Schema::hasColumn('supervisor', 'updated_at')) {
            $updates['updated_at'] = CarbonImmutable::now();
        }

        $this->supervisorQueryById($supervisorId)->update($updates);

        return redirect()
            ->route('account')
            ->with('status', 'Password berhasil diperbarui.');
    }

    public function generate(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'code_type' => ['required', 'in:entry,enter,unlock,exit'],
            'timezone_offset_minutes' => ['nullable', 'integer', 'min:-840', 'max:840'],
        ]);

        $codeType = $this->normalizeCodeType($payload['code_type']);
        $supervisorId = (int) data_get($request->session()->get('supervisor'), 'id', 0);
        $timezoneOffsetMinutes = self::FIXED_TIMEZONE_OFFSET_MINUTES;

        if ($supervisorId <= 0) {
            return redirect()
                ->route('login')
                ->withErrors(['username' => 'Session expired. Please sign in again.']);
        }

        $generatedCode = $this->buildUniqueMixedCode(6);
        $nowLocal = $this->buildLocalNowFromOffset($timezoneOffsetMinutes);

        DB::table('exam_codes')->insert([
            'code' => $generatedCode,
            'codetype' => $codeType,
            'supervisorid' => $supervisorId,
            'expired_at' => $nowLocal->copy()->addMinutes(self::CODE_EXPIRY_MINUTES),
            'created_at' => $nowLocal,
            'updated_at' => $nowLocal,
        ]);

        $request->session()->put('timezone_offset_minutes', self::FIXED_TIMEZONE_OFFSET_MINUTES);

        $statusMessage = match ($codeType) {
            'entry' => 'Entry code generated successfully.',
            'unlock' => 'Unlock code generated successfully.',
            default => 'Exit code generated successfully.',
        };

        return redirect()
            ->route($this->routeNameForCodeType($codeType))
            ->with('status', $statusMessage);
    }

    private function buildPageView(Request $request, string $page): View
    {
        $supervisorId = (int) data_get($request->session()->get('supervisor'), 'id', 0);
        $timezoneOffsetMinutes = $this->resolveTimezoneOffsetMinutes($request);
        $activityPage = max(1, (int) $request->query('activity_page', 1));
        $now = $this->buildLocalNowFromOffset($timezoneOffsetMinutes);
        $expiryCutoff = $now->copy()->subMinutes(self::CODE_EXPIRY_MINUTES);
        $recentActivities = $this->fetchRecentActivities($timezoneOffsetMinutes);
        $activityFeed = $this->fetchRecentActivitiesPaginated($timezoneOffsetMinutes, 20, $activityPage);

        $latestCodes = DB::table('exam_codes')
            ->whereIn('codetype', ['entry', 'unlock', 'exit', 'enter'])
            ->where(function ($query) use ($now, $expiryCutoff): void {
                $query->where('expired_at', '>', $now)
                    ->orWhere(function ($subQuery) use ($expiryCutoff): void {
                        $subQuery->whereNull('expired_at')
                            ->where('created_at', '>=', $expiryCutoff);
                    });
            })
            ->orderByDesc('codeid')
            ->get()
            ->unique('codetype')
            ->keyBy('codetype');

        return view('home', [
            'page' => $page,
            'generatedCodes' => [
                'enter' => $this->formatCodeForView($latestCodes->get('entry') ?? $latestCodes->get('enter'), $timezoneOffsetMinutes),
                'unlock' => $this->formatCodeForView($latestCodes->get('unlock'), $timezoneOffsetMinutes),
                'exit' => $this->formatCodeForView($latestCodes->get('exit'), $timezoneOffsetMinutes),
            ],
            'loginCodeHistory' => $this->fetchGeneratedCodeHistory('entry', $supervisorId, $timezoneOffsetMinutes, 'login_history_page'),
            'exitCodeHistory' => $this->fetchGeneratedCodeHistory('exit', $supervisorId, $timezoneOffsetMinutes, 'exit_history_page'),
            'recentActivities' => $recentActivities,
            'activityFeed' => $activityFeed,
            'violationActivities' => $this->fetchViolationActivities($timezoneOffsetMinutes),
            'codeUsageActivities' => $this->fetchCodeUsageActivities($timezoneOffsetMinutes),
            'supervisorProfile' => $this->fetchSupervisorProfile($supervisorId),
            'unlockCodeHistory' => $this->fetchGeneratedCodeHistory('unlock', $supervisorId, $timezoneOffsetMinutes),
            'timezoneOffsetMinutes' => $timezoneOffsetMinutes,
            'timezoneLabel' => $this->buildUtcOffsetLabel($timezoneOffsetMinutes),
        ]);
    }

    private function fetchViolationActivities(int $timezoneOffsetMinutes, int $limit = 30): array
    {
        if (! Schema::hasTable('violation_logs')) {
            return [];
        }

        $violationColumns = Schema::getColumnListing('violation_logs');
        $hasStudentSessions = Schema::hasTable('student_sessions');
        $hasStudent = Schema::hasTable('student');
        $hasViolationSessionId = in_array('studentsessionid', $violationColumns, true);
        $hasViolationStudentId = in_array('studentid', $violationColumns, true);
        $canJoinSessions = $hasStudentSessions && $hasViolationSessionId;
        $canJoinDirectStudent = $hasStudent && $hasViolationStudentId;
        $canJoinSessionStudent = $hasStudent && $canJoinSessions;

        $query = DB::table('violation_logs as vl');

        if ($canJoinSessions) {
            $query->leftJoin('student_sessions as ss', 'ss.studentsessionid', '=', 'vl.studentsessionid');
        }

        if ($canJoinDirectStudent) {
            $query->leftJoin('student as st_direct', 'st_direct.studentid', '=', 'vl.studentid');
        }

        if ($canJoinSessionStudent) {
            $query->leftJoin('student as st_session', 'st_session.studentid', '=', 'ss.studentid');
        }

        $nisSelect = match (true) {
            $canJoinDirectStudent && $canJoinSessionStudent => 'COALESCE(st_direct.nis, st_session.nis) as nis',
            $canJoinDirectStudent => 'st_direct.nis as nis',
            $canJoinSessionStudent => 'st_session.nis as nis',
            default => 'NULL as nis',
        };

        $nameSelect = match (true) {
            $canJoinDirectStudent && $canJoinSessionStudent => 'COALESCE(st_direct.name, st_session.name) as name',
            $canJoinDirectStudent => 'st_direct.name as name',
            $canJoinSessionStudent => 'st_session.name as name',
            default => 'NULL as name',
        };

        $rows = $query
            ->select([
                DB::raw($nisSelect),
                DB::raw($nameSelect),
                'vl.violation_detail',
                'vl.description',
                'vl.detected_at',
            ])
            ->orderByDesc('vl.detected_at')
            ->limit(max(1, $limit))
            ->get();

        return $rows->map(function (object $row) use ($timezoneOffsetMinutes): array {
            $detail = trim((string) ($row->violation_detail ?? ''));
            $description = trim((string) ($row->description ?? ''));

            return [
                'student_name' => (string) ($row->name ?? '-'),
                'student_nis' => (string) ($row->nis ?? '-'),
                'violation' => $detail !== '' ? $detail : 'Aktivitas terdeteksi',
                'description' => $description !== '' ? $description : '-',
                'detected_at' => $this->formatStoredLocalDateTime($row->detected_at ?? null, $timezoneOffsetMinutes) ?? '-',
            ];
        })->all();
    }

    private function fetchCodeUsageActivities(int $timezoneOffsetMinutes, int $limit = 30): array
    {
        if (! Schema::hasTable('student_sessions') || ! Schema::hasTable('student')) {
            return [];
        }

        $activities = [];

        if (Schema::hasTable('used_code') && Schema::hasTable('exam_codes')) {
            $usedCodeColumns = Schema::getColumnListing('used_code');
            $hasUsedCodeStudentId = in_array('studentid', $usedCodeColumns, true);
            $hasUsedCodeSessionId = in_array('studentsessionid', $usedCodeColumns, true);
            $usedAtExpression = in_array('used_at', $usedCodeColumns, true)
                ? 'uc.used_at'
                : 'COALESCE(ss.entered_at, ec.updated_at, ec.created_at)';

            $usageQuery = DB::table('used_code as uc')
                ->join('exam_codes as ec', 'ec.codeid', '=', 'uc.codeid');

            if ($hasUsedCodeSessionId) {
                $usageQuery->leftJoin('student_sessions as ss', 'ss.studentsessionid', '=', 'uc.studentsessionid');
            } else {
                $usageQuery->leftJoin('student_sessions as ss', DB::raw('1'), '=', DB::raw('0'));
            }

            if ($hasUsedCodeStudentId) {
                $usageQuery->leftJoin('student as st_direct', 'st_direct.studentid', '=', 'uc.studentid');
            } else {
                $usageQuery->leftJoin('student as st_direct', DB::raw('1'), '=', DB::raw('0'));
            }

            $usageQuery->leftJoin('student as st_session', 'st_session.studentid', '=', 'ss.studentid');

            $usageRows = $usageQuery
                ->select([
                    DB::raw('COALESCE(st_direct.nis, st_session.nis) as nis'),
                    DB::raw('COALESCE(st_direct.name, st_session.name) as name'),
                    'ec.code',
                    'ec.codetype',
                    DB::raw($usedAtExpression.' as used_at'),
                ])
                ->where(function ($query): void {
                    $query->whereNotNull('st_direct.studentid')
                        ->orWhereNotNull('st_session.studentid');
                })
                ->orderByDesc('used_at')
                ->limit(max(1, $limit))
                ->get();

            foreach ($usageRows as $row) {
                $activities[] = $this->mapCodeUsageRow($row, $timezoneOffsetMinutes);
            }
        }

        $entrySessionQuery = DB::table('student_sessions as ss')
            ->join('student as st', 'st.studentid', '=', 'ss.studentid')
            ->whereNotNull('ss.entered_at')
            ->select([
                'st.nis',
                'st.name',
                DB::raw("'-' as code"),
                DB::raw("'entry' as codetype"),
                DB::raw('ss.entered_at as used_at'),
            ])
            ->orderByDesc('ss.entered_at')
            ->limit(max(1, $limit));

        if (Schema::hasTable('used_code') && Schema::hasTable('exam_codes')) {
            $entrySessionQuery->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('used_code as uc2')
                    ->join('exam_codes as ec2', 'ec2.codeid', '=', 'uc2.codeid')
                    ->whereColumn('uc2.studentsessionid', 'ss.studentsessionid')
                    ->whereIn('ec2.codetype', ['entry', 'enter']);
            });
        }

        $entrySessionRows = $entrySessionQuery->get();
        foreach ($entrySessionRows as $row) {
            $activities[] = $this->mapCodeUsageRow($row, $timezoneOffsetMinutes);
        }

        usort($activities, function (array $a, array $b): int {
            return strcmp((string) ($b['used_at_raw'] ?? ''), (string) ($a['used_at_raw'] ?? ''));
        });

        $activities = array_slice($activities, 0, max(1, $limit));

        return array_map(function (array $item): array {
            unset($item['used_at_raw']);

            return $item;
        }, $activities);
    }

    private function mapCodeUsageRow(object $row, int $timezoneOffsetMinutes): array
    {
        $codeType = strtolower(trim((string) ($row->codetype ?? '')));
        $codeValue = trim((string) ($row->code ?? ''));

        return [
            'student_name' => (string) ($row->name ?? '-'),
            'student_nis' => (string) ($row->nis ?? '-'),
            'code' => $codeValue !== '' ? $codeValue : '-',
            'code_type' => $codeType !== '' ? strtoupper($codeType) : '-',
            'used_at' => $this->formatStoredLocalDateTime($row->used_at ?? null, $timezoneOffsetMinutes) ?? '-',
            'used_at_raw' => (string) ($row->used_at ?? ''),
        ];
    }

    private function fetchGeneratedCodeHistory(
        string $codeType,
        int $supervisorId,
        int $timezoneOffsetMinutes,
        string $pageName = 'unlock_history_page'
    ): LengthAwarePaginator
    {
        if ($supervisorId <= 0) {
            return DB::table('exam_codes')
                ->whereRaw('1 = 0')
                ->paginate(10, ['*'], $pageName);
        }

        $rows = DB::table('exam_codes')
            ->where('codetype', $codeType)
            ->where('supervisorid', $supervisorId)
            ->orderByDesc('codeid')
            ->paginate(10, ['*'], $pageName);

        $formatted = $rows->getCollection()->map(function (object $row) use ($timezoneOffsetMinutes): array {
            return [
                'code' => (string) ($row->code ?? '-'),
                'generated_at' => $this->formatStoredLocalDateTime($row->created_at ?? null, $timezoneOffsetMinutes) ?? '-',
                'expired_at' => $this->formatStoredLocalDateTime($row->expired_at ?? null, $timezoneOffsetMinutes) ?? '-',
            ];
        });

        $rows->setCollection($formatted);

        return $rows;
    }

    private function fetchSupervisorProfile(int $supervisorId): array
    {
        if ($supervisorId <= 0) {
            return [];
        }

        $row = $this->supervisorQueryById($supervisorId)->first();
        if (! $row) {
            return [];
        }

        return [
            'name' => (string) ($this->readRowValue($row, ['name', 'nama', 'full_name', 'fullname']) ?? ''),
            'username' => (string) ($row->username ?? ''),
            'email' => (string) ($this->readRowValue($row, ['email', 'mail']) ?? ''),
            'phonenumber' => (string) ($this->readRowValue($row, ['phonenumber', 'phone_number', 'phone', 'no_hp', 'nohp', 'hp', 'telp', 'telephone']) ?? ''),
        ];
    }

    private function firstExistingSupervisorColumn(array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn('supervisor', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function readRowValue(object $row, array $candidates): mixed
    {
        foreach ($candidates as $column) {
            if (property_exists($row, $column)) {
                return $row->{$column};
            }
        }

        return null;
    }

    private function supervisorQueryById(int $supervisorId): \Illuminate\Database\Query\Builder
    {
        if (Schema::hasColumn('supervisor', 'supervisorid')) {
            return DB::table('supervisor')->where('supervisorid', $supervisorId);
        }

        if (Schema::hasColumn('supervisor', 'id')) {
            return DB::table('supervisor')->where('id', $supervisorId);
        }

        return DB::table('supervisor')->whereRaw('1 = 0');
    }

    private function fetchRecentActivities(int $timezoneOffsetMinutes): array
    {
        $activities = [];
        $usedCodeColumns = Schema::hasTable('used_code') ? Schema::getColumnListing('used_code') : [];
        $hasStudentSessionsTable = Schema::hasTable('student_sessions');
        $hasStudentTable = Schema::hasTable('student');
        $sessionColumns = $hasStudentSessionsTable ? Schema::getColumnListing('student_sessions') : [];

        if (Schema::hasTable('used_code') && Schema::hasTable('exam_codes')) {
            $hasUsedCodeStudentId = in_array('studentid', $usedCodeColumns, true);
            $hasUsedCodeSessionId = $hasStudentSessionsTable && in_array('studentsessionid', $usedCodeColumns, true);
            $usedAtExpression = in_array('used_at', $usedCodeColumns, true)
                ? 'uc.used_at'
                : (in_array('created_at', $usedCodeColumns, true) ? 'uc.created_at' : 'ec.updated_at');

            $usageQuery = DB::table('used_code as uc')
                ->join('exam_codes as ec', 'ec.codeid', '=', 'uc.codeid');

            if ($hasUsedCodeSessionId) {
                $usageQuery->leftJoin('student_sessions as ss', 'ss.studentsessionid', '=', 'uc.studentsessionid');
            }

            if ($hasStudentTable && $hasUsedCodeStudentId) {
                $usageQuery->leftJoin('student as st_direct', 'st_direct.studentid', '=', 'uc.studentid');
            }

            if ($hasStudentTable && $hasUsedCodeSessionId) {
                $usageQuery->leftJoin('student as st_session', 'st_session.studentid', '=', 'ss.studentid');
            }

            $studentIdSelect = 'NULL as studentid';
            $studentNisSelect = 'NULL as nis';
            $studentNameSelect = 'NULL as name';
            if ($hasStudentTable && $hasUsedCodeStudentId && $hasUsedCodeSessionId) {
                $studentIdSelect = 'COALESCE(st_direct.studentid, st_session.studentid) as studentid';
                $studentNisSelect = 'COALESCE(st_direct.nis, st_session.nis) as nis';
                $studentNameSelect = 'COALESCE(st_direct.name, st_session.name) as name';
            } elseif ($hasStudentTable && $hasUsedCodeStudentId) {
                $studentIdSelect = 'st_direct.studentid as studentid';
                $studentNisSelect = 'st_direct.nis as nis';
                $studentNameSelect = 'st_direct.name as name';
            } elseif ($hasStudentTable && $hasUsedCodeSessionId) {
                $studentIdSelect = 'st_session.studentid as studentid';
                $studentNisSelect = 'st_session.nis as nis';
                $studentNameSelect = 'st_session.name as name';
            }

            $usageRows = $usageQuery
                ->select([
                    DB::raw($studentIdSelect),
                    DB::raw($studentNisSelect),
                    DB::raw($studentNameSelect),
                    'ec.codetype',
                    'ec.code',
                    DB::raw($hasUsedCodeSessionId ? 'uc.studentsessionid as studentsessionid' : 'NULL as studentsessionid'),
                    DB::raw('COALESCE('.$usedAtExpression.', ec.updated_at, ec.created_at) as activity_at'),
                ])
                ->orderByDesc('activity_at')
                ->limit(10)
                ->get();

            foreach ($usageRows as $row) {
                $activities[] = [
                    'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                    'student_nis' => (string) ($row->nis ?: '-'),
                    'action' => $this->buildCodeActionLabel((string) $row->codetype, (string) $row->code),
                    'activity_at' => $this->formatStoredLocalDateTime($row->activity_at, $timezoneOffsetMinutes) ?? '-',
                    'sort_at' => (string) ($row->activity_at ?? ''),
                ];
            }
        }

        if (Schema::hasTable('violation_logs')) {
            $violationColumns = Schema::getColumnListing('violation_logs');
            $hasViolationStudentId = in_array('studentid', $violationColumns, true);
            $violationQuery = DB::table('violation_logs as vl');

            if ($hasStudentTable && $hasViolationStudentId) {
                $violationQuery->leftJoin('student as st', 'st.studentid', '=', 'vl.studentid');
            }

            $violationRows = $violationQuery
                ->select([
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.studentid as studentid' : 'NULL as studentid'),
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.nis as nis' : 'NULL as nis'),
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.name as name' : 'NULL as name'),
                    'vl.violation_detail',
                    'vl.description',
                    'vl.detected_at',
                ])
                ->orderByDesc('vl.detected_at')
                ->limit(10)
                ->get();

            foreach ($violationRows as $row) {
                $detail = strtolower(trim((string) ($row->violation_detail ?? '')));
                $description = strtolower(trim((string) ($row->description ?? '')));
                $isScreenshot = str_contains($detail, 'screenshot')
                    || str_contains($detail, 'screen')
                    || str_contains($description, 'screenshot')
                    || str_contains($description, 'screen');

                $activities[] = [
                    'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                    'student_nis' => (string) ($row->nis ?: '-'),
                    'action' => $isScreenshot
                        ? 'Mencoba screenshot di aplikasi'
                        : 'Pelanggaran: '.((string) ($row->violation_detail ?: 'Aktivitas terdeteksi')),
                    'activity_at' => $this->formatStoredLocalDateTime($row->detected_at, $timezoneOffsetMinutes) ?? '-',
                    'sort_at' => (string) ($row->detected_at ?? ''),
                ];
            }
        }

        if ($hasStudentSessionsTable && $hasStudentTable) {
            $statusExpression = in_array('status', $sessionColumns, true) ? 'ss.status' : DB::raw('NULL as status');
            $sessionRows = DB::table('student_sessions as ss')
                ->join('student as st', 'st.studentid', '=', 'ss.studentid')
                ->select([
                    'st.studentid',
                    'st.nis',
                    'st.name',
                    'ss.entered_at',
                    'ss.exited_at',
                    $statusExpression,
                ])
                ->where(function ($query) use ($sessionColumns): void {
                    $query->whereNotNull('ss.entered_at')
                        ->orWhereNotNull('ss.exited_at');

                    if (in_array('status', $sessionColumns, true)) {
                        $query->orWhereNotNull('ss.status');
                    }
                })
                ->orderByDesc(DB::raw('COALESCE(ss.exited_at, ss.entered_at)'))
                ->limit(10)
                ->get();

            foreach ($sessionRows as $row) {
                $statusText = strtolower(trim((string) ($row->status ?? '')));
                $sessionAction = $this->buildSessionActionLabel($statusText, $row->entered_at, $row->exited_at);
                $activityAt = $row->exited_at ?: $row->entered_at;

                if ($sessionAction && $activityAt) {
                    $activities[] = [
                        'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                        'student_nis' => (string) ($row->nis ?: '-'),
                        'action' => $sessionAction,
                        'activity_at' => $this->formatStoredLocalDateTime($activityAt, $timezoneOffsetMinutes) ?? '-',
                        'sort_at' => (string) $activityAt,
                    ];
                }
            }
        }

        usort($activities, function (array $a, array $b): int {
            return strcmp((string) ($b['sort_at'] ?? ''), (string) ($a['sort_at'] ?? ''));
        });

        return array_map(function (array $item): array {
            unset($item['sort_at']);

            return $item;
        }, array_slice($activities, 0, 5));
    }

    private function fetchRecentActivitiesPaginated(
        int $timezoneOffsetMinutes,
        int $perPage = 20,
        int $currentPage = 1
    ): LengthAwarePaginator {
        $perPage = max(1, $perPage);
        $currentPage = max(1, $currentPage);
        $activities = [];
        $usedCodeColumns = Schema::hasTable('used_code') ? Schema::getColumnListing('used_code') : [];
        $hasStudentSessionsTable = Schema::hasTable('student_sessions');
        $hasStudentTable = Schema::hasTable('student');
        $sessionColumns = $hasStudentSessionsTable ? Schema::getColumnListing('student_sessions') : [];

        if (Schema::hasTable('used_code') && Schema::hasTable('exam_codes')) {
            $hasUsedCodeStudentId = in_array('studentid', $usedCodeColumns, true);
            $hasUsedCodeSessionId = $hasStudentSessionsTable && in_array('studentsessionid', $usedCodeColumns, true);
            $usedAtExpression = in_array('used_at', $usedCodeColumns, true)
                ? 'uc.used_at'
                : (in_array('created_at', $usedCodeColumns, true) ? 'uc.created_at' : 'ec.updated_at');

            $usageQuery = DB::table('used_code as uc')
                ->join('exam_codes as ec', 'ec.codeid', '=', 'uc.codeid');

            if ($hasUsedCodeSessionId) {
                $usageQuery->leftJoin('student_sessions as ss', 'ss.studentsessionid', '=', 'uc.studentsessionid');
            }

            if ($hasStudentTable && $hasUsedCodeStudentId) {
                $usageQuery->leftJoin('student as st_direct', 'st_direct.studentid', '=', 'uc.studentid');
            }

            if ($hasStudentTable && $hasUsedCodeSessionId) {
                $usageQuery->leftJoin('student as st_session', 'st_session.studentid', '=', 'ss.studentid');
            }

            $studentIdSelect = 'NULL as studentid';
            $studentNisSelect = 'NULL as nis';
            $studentNameSelect = 'NULL as name';
            if ($hasStudentTable && $hasUsedCodeStudentId && $hasUsedCodeSessionId) {
                $studentIdSelect = 'COALESCE(st_direct.studentid, st_session.studentid) as studentid';
                $studentNisSelect = 'COALESCE(st_direct.nis, st_session.nis) as nis';
                $studentNameSelect = 'COALESCE(st_direct.name, st_session.name) as name';
            } elseif ($hasStudentTable && $hasUsedCodeStudentId) {
                $studentIdSelect = 'st_direct.studentid as studentid';
                $studentNisSelect = 'st_direct.nis as nis';
                $studentNameSelect = 'st_direct.name as name';
            } elseif ($hasStudentTable && $hasUsedCodeSessionId) {
                $studentIdSelect = 'st_session.studentid as studentid';
                $studentNisSelect = 'st_session.nis as nis';
                $studentNameSelect = 'st_session.name as name';
            }

            $usageRows = $usageQuery
                ->select([
                    DB::raw($studentIdSelect),
                    DB::raw($studentNisSelect),
                    DB::raw($studentNameSelect),
                    'ec.codetype',
                    'ec.code',
                    DB::raw($hasUsedCodeSessionId ? 'uc.studentsessionid as studentsessionid' : 'NULL as studentsessionid'),
                    DB::raw('COALESCE('.$usedAtExpression.', ec.updated_at, ec.created_at) as activity_at'),
                ])
                ->orderByDesc('activity_at')
                ->limit(500)
                ->get();

            foreach ($usageRows as $row) {
                $activities[] = [
                    'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                    'student_nis' => (string) ($row->nis ?: '-'),
                    'action' => $this->buildCodeActionLabel((string) $row->codetype, (string) $row->code),
                    'activity_at' => $this->formatStoredLocalDateTime($row->activity_at, $timezoneOffsetMinutes) ?? '-',
                    'sort_at' => (string) ($row->activity_at ?? ''),
                ];
            }
        }

        if (Schema::hasTable('violation_logs')) {
            $violationColumns = Schema::getColumnListing('violation_logs');
            $hasViolationStudentId = in_array('studentid', $violationColumns, true);
            $violationQuery = DB::table('violation_logs as vl');

            if ($hasStudentTable && $hasViolationStudentId) {
                $violationQuery->leftJoin('student as st', 'st.studentid', '=', 'vl.studentid');
            }

            $violationRows = $violationQuery
                ->select([
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.studentid as studentid' : 'NULL as studentid'),
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.nis as nis' : 'NULL as nis'),
                    DB::raw(($hasStudentTable && $hasViolationStudentId) ? 'st.name as name' : 'NULL as name'),
                    'vl.violation_detail',
                    'vl.description',
                    'vl.detected_at',
                ])
                ->orderByDesc('vl.detected_at')
                ->limit(500)
                ->get();

            foreach ($violationRows as $row) {
                $detail = strtolower(trim((string) ($row->violation_detail ?? '')));
                $description = strtolower(trim((string) ($row->description ?? '')));
                $isScreenshot = str_contains($detail, 'screenshot')
                    || str_contains($detail, 'screen')
                    || str_contains($description, 'screenshot')
                    || str_contains($description, 'screen');

                $activities[] = [
                    'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                    'student_nis' => (string) ($row->nis ?: '-'),
                    'action' => $isScreenshot
                        ? 'Mencoba screenshot di aplikasi'
                        : 'Pelanggaran: '.((string) ($row->violation_detail ?: 'Aktivitas terdeteksi')),
                    'activity_at' => $this->formatStoredLocalDateTime($row->detected_at, $timezoneOffsetMinutes) ?? '-',
                    'sort_at' => (string) ($row->detected_at ?? ''),
                ];
            }
        }

        if ($hasStudentSessionsTable && $hasStudentTable) {
            $statusExpression = in_array('status', $sessionColumns, true) ? 'ss.status' : DB::raw('NULL as status');
            $sessionRows = DB::table('student_sessions as ss')
                ->join('student as st', 'st.studentid', '=', 'ss.studentid')
                ->select([
                    'st.studentid',
                    'st.nis',
                    'st.name',
                    'ss.entered_at',
                    'ss.exited_at',
                    $statusExpression,
                ])
                ->where(function ($query) use ($sessionColumns): void {
                    $query->whereNotNull('ss.entered_at')
                        ->orWhereNotNull('ss.exited_at');

                    if (in_array('status', $sessionColumns, true)) {
                        $query->orWhereNotNull('ss.status');
                    }
                })
                ->orderByDesc(DB::raw('COALESCE(ss.exited_at, ss.entered_at)'))
                ->limit(500)
                ->get();

            foreach ($sessionRows as $row) {
                $statusText = strtolower(trim((string) ($row->status ?? '')));
                $sessionAction = $this->buildSessionActionLabel($statusText, $row->entered_at, $row->exited_at);
                $activityAt = $row->exited_at ?: $row->entered_at;

                if ($sessionAction && $activityAt) {
                    $activities[] = [
                        'student_name' => (string) ($row->name ?: 'Student #'.($row->studentid ?? '-')),
                        'student_nis' => (string) ($row->nis ?: '-'),
                        'action' => $sessionAction,
                        'activity_at' => $this->formatStoredLocalDateTime($activityAt, $timezoneOffsetMinutes) ?? '-',
                        'sort_at' => (string) $activityAt,
                    ];
                }
            }
        }

        usort($activities, function (array $a, array $b): int {
            return strcmp((string) ($b['sort_at'] ?? ''), (string) ($a['sort_at'] ?? ''));
        });

        $normalized = array_values(array_map(function (array $item): array {
            unset($item['sort_at']);

            return $item;
        }, $activities));

        $total = count($normalized);
        $offset = ($currentPage - 1) * $perPage;
        $items = array_slice($normalized, $offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'activity_page',
                'query' => request()->query(),
            ]
        );
    }

    private function buildCodeActionLabel(string $codeType, string $code): string
    {
        $normalized = $this->normalizeCodeType($codeType);
        $label = match ($normalized) {
            'entry' => 'Menggunakan Login Code',
            'unlock' => 'Menggunakan Unlock Code',
            'exit' => 'Menggunakan Exit Code',
            default => 'Menggunakan Code',
        };

        return $label.' ('.$code.')';
    }

    private function buildSessionActionLabel(string $status, mixed $enteredAt, mixed $exitedAt): ?string
    {
        if ($status !== '' && (str_contains($status, 'screenshot') || str_contains($status, 'screen_shot'))) {
            return 'Mencoba screenshot di aplikasi';
        }

        if (!empty($exitedAt)) {
            return 'Keluar dari sesi aplikasi';
        }

        if (!empty($enteredAt)) {
            return 'Masuk ke sesi aplikasi';
        }

        return null;
    }

    private function routeNameForCodeType(string $codeType): string
    {
        return match ($codeType) {
            'entry' => 'code.login',
            'unlock' => 'code.unlock',
            'exit' => 'code.exit',
            default => 'home',
        };
    }

    private function buildUniqueMixedCode(int $length): string
    {
        do {
            $code = $this->buildMixedCode($length);
        } while (DB::table('exam_codes')->where('code', $code)->exists());

        return $code;
    }

    private function buildMixedCode(int $length): string
    {
        $length = max(2, $length);
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';

            for ($i = 0; $i < $length; $i++) {
                $code .= $pool[random_int(0, strlen($pool) - 1)];
            }
        } while (!preg_match('/[A-Z]/', $code) || !preg_match('/\d/', $code));

        return Str::upper($code);
    }

    private function formatCodeForView(?object $code, int $timezoneOffsetMinutes): array
    {
        if (!$code) {
            return [];
        }

        return [
            'code' => $code->code,
            'generated_at' => $this->formatStoredLocalDateTime($code->created_at ?? null, $timezoneOffsetMinutes),
            'expired_at' => $this->formatStoredLocalDateTime($code->expired_at ?? null, $timezoneOffsetMinutes),
        ];
    }

    private function resolveTimezoneOffsetMinutes(Request $request): int
    {
        return self::FIXED_TIMEZONE_OFFSET_MINUTES;
    }

    private function buildUtcOffsetLabel(int $timezoneOffsetMinutes): string
    {
        $localOffsetMinutes = -$timezoneOffsetMinutes;
        $sign = $localOffsetMinutes >= 0 ? '+' : '-';
        $absMinutes = abs($localOffsetMinutes);
        $hours = str_pad((string) intdiv($absMinutes, 60), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) ($absMinutes % 60), 2, '0', STR_PAD_LEFT);

        return 'UTC'.$sign.$hours.':'.$minutes;
    }

    private function formatStoredLocalDateTime(mixed $dateTime, int $timezoneOffsetMinutes): ?string
    {
        if (empty($dateTime)) {
            return null;
        }

        $timezone = $this->buildOffsetTimezone($timezoneOffsetMinutes);
        $local = CarbonImmutable::parse((string) $dateTime, $timezone);

        return $local->format('d M Y H:i:s');
    }

    private function buildLocalNowFromOffset(int $timezoneOffsetMinutes): CarbonImmutable
    {
        return CarbonImmutable::now('UTC')->subMinutes($timezoneOffsetMinutes);
    }

    private function buildOffsetTimezone(int $timezoneOffsetMinutes): string
    {
        $localOffsetMinutes = -$timezoneOffsetMinutes;
        $sign = $localOffsetMinutes >= 0 ? '+' : '-';
        $absMinutes = abs($localOffsetMinutes);
        $hours = str_pad((string) intdiv($absMinutes, 60), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string) ($absMinutes % 60), 2, '0', STR_PAD_LEFT);

        return $sign.$hours.':'.$minutes;
    }

    private function normalizeCodeType(string $codeType): string
    {
        $codeType = strtolower(trim($codeType));

        return $codeType === 'enter' ? 'entry' : $codeType;
    }
}
