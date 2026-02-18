#!/bin/bash
# Generate release keystore for Play Store

# Configuration
KEYSTORE_PATH="$HOME/dossy_release.jks"
ALIAS="dossy_key"
VALIDITY=10000
KEY_SIZE=2048

# Génération
keytool -genkey -v \
  -keystore "$KEYSTORE_PATH" \
  -keyalg RSA \
  -keysize $KEY_SIZE \
  -validity $VALIDITY \
  -alias $ALIAS \
  -dname "CN=DOSSY Pro,OU=DOSSY,O=DOSSY Pro,L=Abidjan,ST=Lagunes,C=CI" \
  -storepass "DossyPro@2026Release" \
  -keypass "DossyPro@2026Key"

echo "✅ Keystore créé: $KEYSTORE_PATH"
echo "Alias: $ALIAS"
echo "Store Password: DossyPro@2026Release"
echo "Key Password: DossyPro@2026Key"
