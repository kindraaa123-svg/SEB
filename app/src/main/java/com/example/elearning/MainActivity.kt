package com.example.elearning

import android.content.Intent
import android.graphics.Bitmap
import android.os.Build
import android.os.Bundle
import android.os.SystemClock
import android.view.View
import android.view.WindowManager
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Button
import android.widget.EditText
import android.widget.ImageButton
import android.widget.ProgressBar
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import org.json.JSONObject
import java.io.IOException
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class MainActivity : AppCompatActivity() {

    private lateinit var webView: WebView
    private lateinit var progressBar: ProgressBar
    private var screenCaptureCallback: android.app.Activity.ScreenCaptureCallback? = null
    private var webViewBackCallback: OnBackPressedCallback? = null
    private var currentNis: String? = null
    private var currentTargetUrl: String = DEFAULT_TARGET_URL

    private var isAuthorizedExitFlow = false
    private var isInProtectedMode = false
    private var requireUnlockOnResume = false
    private var isUnlockScreenVisible = false
    private var lastViolationSignature = ""
    private var lastViolationTimeMs = 0L

    companion object {
        private const val DEFAULT_TARGET_URL = "https://elsph.permataharapanku.sch.id/"
        private const val LOCAL_API_BASE_URL = "http://10.0.2.2/SEB/"
        private val ENTRY_VALIDATION_URLS = listOf(
            LOCAL_API_BASE_URL + "validate_entry_code.php"
        )
        private val EXIT_VALIDATION_URLS = listOf(
            LOCAL_API_BASE_URL + "validate_exit_code.php"
        )
        private val UNLOCK_VALIDATION_URLS = listOf(
            LOCAL_API_BASE_URL + "validate_unlock_code.php"
        )
        private val VIOLATION_LOG_URLS = listOf(
            LOCAL_API_BASE_URL + "log_violation.php"
        )
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        window.setFlags(
            WindowManager.LayoutParams.FLAG_SECURE,
            WindowManager.LayoutParams.FLAG_SECURE
        )
        enableEdgeToEdge()
        setupScreenCaptureDetection()
        showLoginScreen()
    }

    override fun onResume() {
        super.onResume()
        if (isInProtectedMode && requireUnlockOnResume && !isAuthorizedExitFlow && !isUnlockScreenVisible) {
            showUnlockScreen()
        }
    }

    override fun onUserLeaveHint() {
        super.onUserLeaveHint()
        if (isInProtectedMode && !isAuthorizedExitFlow) {
            handleViolation(
                type = "LEAVE_APP_ATTEMPT",
                detail = "User mencoba keluar/pindah aplikasi dari protected webview"
            )
            forceReturnToApp()
        }
    }

    override fun onStop() {
        super.onStop()
        if (isInProtectedMode && !isAuthorizedExitFlow && !isChangingConfigurations && !isFinishing) {
            requireUnlockOnResume = true
            handleViolation(
                type = "APP_BACKGROUND",
                detail = "Aplikasi masuk background saat sesi ujian aktif"
            )
            forceReturnToApp()
        }
    }

    override fun onPause() {
        super.onPause()
        if (isInProtectedMode && !isAuthorizedExitFlow) {
            forceReturnToApp()
        }
    }

    private fun showLoginScreen() {
        isAuthorizedExitFlow = false
        isInProtectedMode = false
        requireUnlockOnResume = false
        isUnlockScreenVisible = false
        setContentView(R.layout.activity_main)
        currentNis = null
        currentTargetUrl = DEFAULT_TARGET_URL

        val examCodeInput = findViewById<EditText>(R.id.etExamCode)
        val nisInput = findViewById<EditText>(R.id.etNis)
        val diveInButton = findViewById<Button>(R.id.btnDiveIn)

        diveInButton.setOnClickListener {
            val examCode = examCodeInput.text?.toString()?.trim().orEmpty()
            val nis = nisInput.text?.toString()?.trim().orEmpty()

            if (examCode.isEmpty()) {
                examCodeInput.error = "Exam code wajib diisi"
                examCodeInput.requestFocus()
                return@setOnClickListener
            }

            if (nis.isEmpty()) {
                nisInput.error = "NIS wajib diisi"
                nisInput.requestFocus()
                return@setOnClickListener
            }

            validateEntryCode(examCode, nis, diveInButton)
        }
    }

    private fun validateEntryCode(examCode: String, nis: String, button: Button) {
        button.isEnabled = false
        button.text = "Checking..."

        Thread {
            var responseMessage = "Tidak bisa menghubungi server. Periksa internet/server API."
            var success = false
            var targetLink = DEFAULT_TARGET_URL

            try {
                val payload = buildString {
                    append("exam_code=").append(urlEncode(examCode))
                    append("&code=").append(urlEncode(examCode))
                    append("&nis=").append(urlEncode(nis))
                }
                val body = postToAnyUrl(ENTRY_VALIDATION_URLS, payload)
                val json = JSONObject(body)

                success = json.optBoolean("success", false)
                responseMessage = json.optString("message", "Respon server tidak valid")
            } catch (e: Exception) {
                responseMessage = e.message?.takeIf { it.isNotBlank() }
                    ?: "Tidak bisa menghubungi server. Periksa XAMPP Apache dan endpoint API."
            }

            runOnUiThread {
                button.isEnabled = true
                button.text = "Dive In"

                if (success) {
                    currentNis = nis
                    currentTargetUrl = targetLink
                    showWebViewScreen(targetLink)
                } else {
                    Toast.makeText(this, responseMessage, Toast.LENGTH_SHORT).show()
                }
            }
        }.start()
    }

    private fun validateExitCode(exitCode: String, nis: String, onDone: (Boolean, String) -> Unit) {
        Thread {
            var responseMessage = "Tidak bisa menghubungi server. Periksa internet/server API."
            var success = false

            try {
                val payload = buildString {
                    append("exit_code=").append(urlEncode(exitCode))
                    append("&code=").append(urlEncode(exitCode))
                    append("&nis=").append(urlEncode(nis))
                }
                val body = postToAnyUrl(EXIT_VALIDATION_URLS, payload)
                val json = JSONObject(body)

                success = json.optBoolean("success", false)
                responseMessage = json.optString("message", "Respon server tidak valid")
            } catch (_: Exception) {
            }

            runOnUiThread {
                onDone(success, responseMessage)
            }
        }.start()
    }

    private fun validateUnlockCode(unlockCode: String, nis: String, onDone: (Boolean, String) -> Unit) {
        Thread {
            var responseMessage = "Tidak bisa menghubungi server. Periksa internet/server API."
            var success = false

            try {
                val payload = buildString {
                    append("unlock_code=").append(urlEncode(unlockCode))
                    append("&code=").append(urlEncode(unlockCode))
                    append("&nis=").append(urlEncode(nis))
                }
                val body = postToAnyUrl(UNLOCK_VALIDATION_URLS, payload)
                val json = JSONObject(body)

                success = json.optBoolean("success", false)
                responseMessage = json.optString("message", "Respon server tidak valid")
            } catch (_: Exception) {
            }

            runOnUiThread {
                onDone(success, responseMessage)
            }
        }.start()
    }

    @Throws(IOException::class)
    private fun postToAnyUrl(urls: List<String>, payload: String): String {
        var lastError: Exception? = null

        for (endpoint in urls) {
            try {
                val conn = (URL(endpoint).openConnection() as HttpURLConnection).apply {
                    requestMethod = "POST"
                    connectTimeout = 10000
                    readTimeout = 10000
                    doOutput = true
                    setRequestProperty("Content-Type", "application/x-www-form-urlencoded")
                    setRequestProperty("Accept", "application/json")
                }

                OutputStreamWriter(conn.outputStream).use { writer ->
                    writer.write(payload)
                    writer.flush()
                }

                val stream = if (conn.responseCode in 200..299) conn.inputStream else conn.errorStream
                val body = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
                conn.disconnect()

                if (body.isNotBlank()) {
                    return body
                }
            } catch (e: Exception) {
                lastError = e
            }
        }

        throw IOException("Semua endpoint gagal dihubungi", lastError)
    }

    private fun urlEncode(value: String): String {
        return URLEncoder.encode(value, Charsets.UTF_8.name())
    }

    private fun showWebViewScreen(url: String) {
        setContentView(R.layout.home_activity)

        isAuthorizedExitFlow = false
        isInProtectedMode = true
        isUnlockScreenVisible = false
        currentTargetUrl = url
        enableExamKioskMode()

        webView = findViewById(R.id.webView)
        progressBar = findViewById(R.id.progressBar)
        val exitButton = findViewById<ImageButton>(R.id.btnExit)

        webView.settings.javaScriptEnabled = true
        webView.settings.domStorageEnabled = true

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(
                view: WebView?,
                request: WebResourceRequest?
            ): Boolean {
                val nextUrl = request?.url?.toString() ?: return false
                view?.loadUrl(nextUrl)
                return true
            }

            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                progressBar.visibility = View.VISIBLE
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                progressBar.visibility = View.GONE
            }
        }

        exitButton.setOnClickListener {
            showExitCodeDialog()
        }

        webView.loadUrl(url)

        webViewBackCallback?.remove()
        webViewBackCallback = object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                handleViolation(
                    type = "BACK_BLOCKED",
                    detail = "Tombol back diblokir di protected webview"
                )
                showExitCodeDialog()
            }
        }
        onBackPressedDispatcher.addCallback(this, webViewBackCallback!!)

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom)
            insets
        }
    }

    private fun showExitCodeDialog() {
        val activeNis = currentNis
        if (activeNis.isNullOrBlank()) {
            Toast.makeText(this, "Sesi user tidak ditemukan, silakan login ulang", Toast.LENGTH_SHORT).show()
            isAuthorizedExitFlow = true
            showLoginScreen()
            return
        }

        val dialogView = layoutInflater.inflate(R.layout.dialog_exit, null)
        val etExitCode = dialogView.findViewById<EditText>(R.id.etExitCode)
        val btnCancel = dialogView.findViewById<Button>(R.id.btnCancel)
        val btnConfirmExit = dialogView.findViewById<Button>(R.id.btnConfirmExit)

        val dialog = AlertDialog.Builder(this)
            .setView(dialogView)
            .setCancelable(false)
            .create()

        dialog.window?.setBackgroundDrawableResource(android.R.color.transparent)

        btnCancel.setOnClickListener {
            dialog.dismiss()
        }

        btnConfirmExit.setOnClickListener {
            val exitCode = etExitCode.text?.toString()?.trim().orEmpty()
            if (exitCode.isEmpty()) {
                etExitCode.error = "Exit code wajib diisi"
                etExitCode.requestFocus()
                return@setOnClickListener
            }

            btnConfirmExit.isEnabled = false
            btnConfirmExit.text = "Checking..."

            validateExitCode(exitCode, activeNis) { success, message ->
                if (success) {
                    isAuthorizedExitFlow = true
                    isInProtectedMode = false
                    requireUnlockOnResume = false
                    isUnlockScreenVisible = false
                    disableExamKioskMode()
                    if (::webView.isInitialized) {
                        webView.stopLoading()
                        webView.loadUrl("about:blank")
                        webView.clearHistory()
                    }
                    dialog.dismiss()
                    showLoginScreen()
                } else {
                    btnConfirmExit.isEnabled = true
                    btnConfirmExit.text = "Keluar"
                    Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
                }
            }
        }

        dialog.show()
    }

    private fun showUnlockScreen() {
        val activeNis = currentNis
        if (activeNis.isNullOrBlank()) {
            showLoginScreen()
            return
        }

        setContentView(R.layout.unlock_activity)
        isUnlockScreenVisible = true

        val unlockCodeInput = findViewById<EditText>(R.id.etExamCode)
        val unlockButton = findViewById<Button>(R.id.btnDiveIn)

        unlockButton.setOnClickListener {
            val unlockCode = unlockCodeInput.text?.toString()?.trim().orEmpty()
            if (unlockCode.isEmpty()) {
                unlockCodeInput.error = "Unlock code wajib diisi"
                unlockCodeInput.requestFocus()
                return@setOnClickListener
            }

            unlockButton.isEnabled = false
            unlockButton.text = "Checking..."

            validateUnlockCode(unlockCode, activeNis) { success, message ->
                if (success) {
                    requireUnlockOnResume = false
                    isUnlockScreenVisible = false
                    showWebViewScreen(currentTargetUrl)
                } else {
                    unlockButton.isEnabled = true
                    unlockButton.text = "Dive In"
                    Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
                }
            }
        }
    }

    private fun setupScreenCaptureDetection() {
        if (android.os.Build.VERSION.SDK_INT < android.os.Build.VERSION_CODES.UPSIDE_DOWN_CAKE) {
            return
        }

        val callback = android.app.Activity.ScreenCaptureCallback {
            onScreenCaptureDetected("Upaya screenshot/screen record terdeteksi")
        }
        screenCaptureCallback = callback
        registerScreenCaptureCallback(ContextCompat.getMainExecutor(this), callback)
    }

    private fun onScreenCaptureDetected(message: String) {
        appendCaptureHistory(message)
        handleViolation("SCREEN_CAPTURE", message)
        if (!isFinishing && !isDestroyed) {
            AlertDialog.Builder(this)
                .setTitle("Peringatan Keamanan")
                .setMessage("Screenshot atau screen recording tidak diizinkan pada halaman ini.")
                .setPositiveButton("OK", null)
                .show()
        }
    }

    private fun handleViolation(type: String, detail: String) {


        val now = SystemClock.elapsedRealtime()
        val signature = "$type|$detail"
        if (signature == lastViolationSignature && now - lastViolationTimeMs < 1500) {
            return
        }
        lastViolationSignature = signature
        lastViolationTimeMs = now

        appendViolationHistory(type, detail)
        sendViolationToServer(type, detail)
    }

    private fun sendViolationToServer(type: String, detail: String) {
        val nis = currentNis.orEmpty()

        Thread {
            try {
                val payload = buildString {
                    append("nis=").append(urlEncode(nis))
                    append("&violation_detail=").append(urlEncode(type))
                    append("&description=").append(urlEncode(detail))
                    append("&violation_type=").append(urlEncode(type))
                    append("&detail=").append(urlEncode(detail))
                }
                postToAnyUrl(VIOLATION_LOG_URLS, payload)
            } catch (_: Exception) {
            }
        }.start()
    }

    private fun forceReturnToApp() {
        try {
            val intent = Intent(this, MainActivity::class.java).apply {
                addFlags(Intent.FLAG_ACTIVITY_REORDER_TO_FRONT or Intent.FLAG_ACTIVITY_SINGLE_TOP)
            }
            startActivity(intent)
        } catch (_: Exception) {
        }
    }

    private fun enableExamKioskMode() {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                startLockTask()
            }
        } catch (_: Exception) {
        }
    }

    private fun disableExamKioskMode() {
        try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                stopLockTask()
            }
        } catch (_: Exception) {
        }
    }





    private fun appendCaptureHistory(event: String) {
        val timestamp = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault()).format(Date())
        val entry = "$timestamp | $event"
        try {
            openFileOutput("capture_history.log", MODE_APPEND).bufferedWriter().use { writer ->
                writer.appendLine(entry)
            }
        } catch (_: IOException) {
        }
    }

    private fun appendViolationHistory(type: String, detail: String) {
        val timestamp = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault()).format(Date())
        val entry = "$timestamp | $type | $detail"
        try {
            openFileOutput("violation_history.log", MODE_APPEND).bufferedWriter().use { writer ->
                writer.appendLine(entry)
            }
        } catch (_: IOException) {
        }
    }

    override fun onDestroy() {
        if (isInProtectedMode) {
            disableExamKioskMode()
        }
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.UPSIDE_DOWN_CAKE) {
            screenCaptureCallback?.let { unregisterScreenCaptureCallback(it) }
        }

        if (::webView.isInitialized) {
            webView.destroy()
        }
        super.onDestroy()
    }
}






