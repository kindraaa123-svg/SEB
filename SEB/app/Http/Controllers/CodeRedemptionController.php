<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CodeRedemptionController extends Controller
{
    private const CODE_EXPIRY_MINUTES = 10;
    private const DEFAULT_TIMEZONE_OFFSET_MINUTES = -420;

    public function redeem(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'student_id' => ['required', 'string', 'max:100'],
            'code_type' => ['nullable', 'in:entry,enter,unlock,exit'],
            'timezone_offset_minutes' => ['nullable', 'integer', 'min:-840', 'max:840'],
        ]);

        $studentId = (int) trim($payload['student_id']);
        $code = strtoupper(trim($payload['code']));
        $codeType = !empty($payload['code_type']) ? $this->normalizeCodeType($payload['code_type']) : null;
        $timezoneOffsetMinutes = self::DEFAULT_TIMEZONE_OFFSET_MINUTES;

        $query = DB::table('exam_codes')
            ->where('code', $code)
            ->orderByDesc('codeid');

        if (!empty($codeType)) {
            $query->where('codetype', $codeType);
        }

        $accessCode = $query->first();

        if (!$accessCode) {
            return response()->json([
                'message' => 'Code not found or inactive.',
            ], 404);
        }

        $nowTimestamp = $this->buildLocalNowFromOffset($timezoneOffsetMinutes)->timestamp;
        $expiredAt = $this->parseStoredDateTimeToTimestamp($accessCode->expired_at ?? null, $timezoneOffsetMinutes);
        $createdAt = $this->parseStoredDateTimeToTimestamp($accessCode->created_at ?? null, $timezoneOffsetMinutes);
        $fallbackExpiry = $createdAt !== false ? $createdAt + (self::CODE_EXPIRY_MINUTES * 60) : false;

        if (($expiredAt !== false && $expiredAt <= $nowTimestamp) ||
            ($expiredAt === false && ($fallbackExpiry === false || $fallbackExpiry <= $nowTimestamp))) {
            return response()->json([
                'message' => 'Code has expired.',
            ], 410);
        }

        $studentExists = DB::table('student')
            ->where('studentid', $studentId)
            ->exists();

        if (!$studentExists) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }

        $alreadyUsed = DB::table('used_code')
            ->where('codeid', (int) $accessCode->codeid)
            ->where('studentid', $studentId)
            ->exists();

        if ($alreadyUsed) {
            return response()->json([
                'message' => 'This student has already used this code.',
            ], 409);
        }

        try {
            $usedCodeColumns = Schema::hasTable('used_code') ? Schema::getColumnListing('used_code') : [];
            $insertPayload = [
                'codeid' => (int) $accessCode->codeid,
                'studentid' => $studentId,
            ];

            if (in_array('used_at', $usedCodeColumns, true)) {
                $insertPayload['used_at'] = now();
            }

            DB::table('used_code')->insert($insertPayload);
        } catch (QueryException $exception) {
            // SQLSTATE 23000 = integrity constraint violation (duplicate unique pair).
            if ((string) $exception->getCode() === '23000') {
                return response()->json([
                    'message' => 'This student has already used this code.',
                ], 409);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Code redeemed successfully.',
            'data' => [
                'code_type' => $accessCode->codetype,
                'code' => $accessCode->code,
                'used_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function normalizeCodeType(string $codeType): string
    {
        $codeType = strtolower(trim($codeType));

        return $codeType === 'enter' ? 'entry' : $codeType;
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

    private function parseStoredDateTimeToTimestamp(mixed $dateTime, int $timezoneOffsetMinutes): int|false
    {
        if (empty($dateTime)) {
            return false;
        }

        $timezone = $this->buildOffsetTimezone($timezoneOffsetMinutes);

        return CarbonImmutable::parse((string) $dateTime, $timezone)->timestamp;
    }
}
