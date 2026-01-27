pluginManagement {
    val flutterSdkPath =
        run {
            val properties = java.util.Properties()
            file("local.properties").inputStream().use { properties.load(it) }
            val flutterSdkPath = properties.getProperty("flutter.sdk")
            require(flutterSdkPath != null) { "flutter.sdk not set in local.properties" }
            // Warn if engine artifacts are missing (helps debugging missing io.flutter/* artifacts)
            val engineArtifactsDir = file("${flutterSdkPath}/bin/cache/artifacts/engine/android")
            if (!engineArtifactsDir.exists()) {
                println("WARNING: Flutter engine artifacts not found at: ${engineArtifactsDir}. Run `flutter precache` or delete SDK 'bin/cache' and run `flutter doctor` to re-download artifacts.")
            }
            flutterSdkPath
        }

    includeBuild("$flutterSdkPath/packages/flutter_tools/gradle")

    repositories {
        google()
        mavenCentral()
        // Add Flutter engine artifacts (local SDK cache) to resolve io.flutter/* debug artifacts
        maven { url = uri("$flutterSdkPath/bin/cache/artifacts/engine/android") }
        maven { url = uri("https://maven.aliyun.com/repository/public") }
        gradlePluginPortal()
    }
}

plugins {
    id("dev.flutter.flutter-plugin-loader") version "1.0.0"
    id("com.android.application") version "8.11.1" apply false
    id("org.jetbrains.kotlin.android") version "2.2.20" apply false
}

include(":app")

// Ensure all project dependencies can resolve from these repositories (use local/mirror as fallback)
dependencyResolutionManagement {
    // Allow plugins and build scripts to add repositories but prefer the settings repositories
    repositoriesMode.set(RepositoriesMode.PREFER_SETTINGS)
    repositories {
        mavenLocal()
        google()
        mavenCentral()
        // Add Flutter engine artifacts (local SDK cache) to resolve io.flutter/* debug artifacts
        maven { url = uri("$flutterSdkPath/bin/cache/artifacts/engine/android") }
        maven { url = uri("https://maven.aliyun.com/repository/public") }
    }
}
