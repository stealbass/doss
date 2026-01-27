import 'app_constants.dart';

/// Lightweight shim so older code that imports `api_constants.dart` continues
/// to work while the canonical values are kept in `AppConstants`.
class ApiConstants {
  static const String baseUrl = AppConstants.baseUrl;
  static const String apiBaseUrl = AppConstants.apiBaseUrl;
  static const String filesBaseUrl = AppConstants.filesBaseUrl;
}
