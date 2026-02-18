# Generate release keystore for Play Store
$keystorePath = "$env:USERPROFILE\dossy_release.jks"
$alias = "dossy_key"
$validity = 10000
$keySize = 2048
$storePassword = "DossyPro@2026Release"
$keyPassword = "DossyPro@2026Key"

# Génération
keytool -genkey -v `
  -keystore $keystorePath `
  -keyalg RSA `
  -keysize $keySize `
  -validity $validity `
  -alias $alias `
  -dname "CN=DOSSY Pro,OU=DOSSY,O=DOSSY Pro,L=Abidjan,ST=Lagunes,C=CI" `
  -storepass $storePassword `
  -keypass $keyPassword

Write-Host "✅ Keystore créé: $keystorePath"
Write-Host "Alias: $alias"
Write-Host "Store Password: $storePassword"
Write-Host "Key Password: $keyPassword"
