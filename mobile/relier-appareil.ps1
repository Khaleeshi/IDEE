# Relie la tablette ou le téléphone branché en USB au serveur IDEE de ce PC.
# À lancer après avoir branché l'appareil, et à relancer s'il a été débranché.
# Ensuite, sur l'appareil, http://127.0.0.1:8000 mène au serveur du PC :
# l'application (configuration « IDEE - tablette ou téléphone (USB) ») et le
# site dans Chrome fonctionnent sans Wi-Fi ni réglage du pare-feu.

$adb = Join-Path $env:LOCALAPPDATA 'Android\Sdk\platform-tools\adb.exe'
if (-not (Test-Path $adb)) {
    Write-Host "adb introuvable. Dans Android Studio : SDK Manager > SDK Tools > Android SDK Platform-Tools." -ForegroundColor Red
    exit 1
}

$devices = & $adb devices | Select-Object -Skip 1 | Where-Object { $_ -match '\tdevice$' }
if (-not $devices) {
    Write-Host "Aucun appareil autorisé. Branchez-le, activez le débogage USB et acceptez la demande sur l'écran de l'appareil." -ForegroundColor Yellow
    & $adb devices
    exit 1
}

& $adb reverse tcp:8000 tcp:8000
Write-Host "Appareil relié : sur la tablette, http://127.0.0.1:8000 mène au serveur IDEE de ce PC." -ForegroundColor Green
Write-Host "Pensez à laisser « php artisan serve » ouvert dans le dossier backend."
