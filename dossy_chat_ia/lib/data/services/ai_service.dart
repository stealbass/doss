import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../core/constants/app_constants.dart';

/// Service for AI-powered features
class AIService {
  final String baseUrl = AppConstants.apiBaseUrl;
  final http.Client client;

  AIService({http.Client? client}) : client = client ?? http.Client();

  /// Generate Fiche d'Arrêt using AI
  /// 
  /// [caseText] - Full text of the legal case
  /// [jurisdiction] - Jurisdiction code (CI, SN, etc.)
  /// [domain] - Legal domain (civil, penal, etc.)
  /// [token] - User authentication token
  Future<Map<String, dynamic>> generateFicheArret({
    required String caseText,
    required String jurisdiction,
    required String domain,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/fiche-arret'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'case_text': caseText,
          'jurisdiction': jurisdiction,
          'domain': domain,
        }),
      ).timeout(const Duration(seconds: 60));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'data': data['data'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la génération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Generate QCM (Multiple Choice Questions)
  /// 
  /// [domain] - Legal domain
  /// [numberOfQuestions] - Number of questions to generate (5-30)
  /// [difficulty] - Difficulty level (easy, medium, hard)
  /// [courseContent] - Optional course content for context
  /// [token] - User authentication token
  Future<Map<String, dynamic>> generateQCM({
    required String domain,
    required int numberOfQuestions,
    required String difficulty,
    String? courseContent,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/qcm'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'domain': domain,
          'number_of_questions': numberOfQuestions,
          'difficulty': difficulty,
          'course_content': courseContent,
        }),
      ).timeout(const Duration(seconds: 60));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'questions': data['questions'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la génération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Anonymize document (detect and redact sensitive information)
  /// 
  /// [documentId] - ID of uploaded document
  /// [token] - User authentication token
  Future<Map<String, dynamic>> anonymizeDocument({
    required String documentId,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/anonymize'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'document_id': documentId,
        }),
      ).timeout(const Duration(seconds: 90));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'detections': data['detections'],
          'anonymized_url': data['anonymized_url'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'anonymisation',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Transcribe audio file to text
  /// 
  /// [audioId] - ID of uploaded audio file
  /// [language] - Language code (fr, en)
  /// [token] - User authentication token
  Future<Map<String, dynamic>> transcribeAudio({
    required String audioId,
    String language = 'fr',
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/transcribe'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'audio_id': audioId,
          'language': language,
        }),
      ).timeout(const Duration(seconds: 120));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'transcription': data['transcription'],
          'timestamps': data['timestamps'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la transcription',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Get AI chat response with context
  /// 
  /// [message] - User message
  /// [jurisdiction] - User's jurisdiction
  /// [conversationId] - Optional conversation ID for context
  /// [token] - User authentication token
  Future<Map<String, dynamic>> getChatResponse({
    required String message,
    required String jurisdiction,
    String? conversationId,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/chat'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'message': message,
          'jurisdiction': jurisdiction,
          'conversation_id': conversationId,
        }),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'response': data['response'],
          'conversation_id': data['conversation_id'],
          'sources': data['sources'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la réponse',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Analyze document with AI
  /// 
  /// [documentId] - ID of uploaded document
  /// [analysisType] - Type of analysis (summary, key_points, legal_issues)
  /// [token] - User authentication token
  Future<Map<String, dynamic>> analyzeDocument({
    required String documentId,
    required String analysisType,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/analyze'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'document_id': documentId,
          'analysis_type': analysisType,
        }),
      ).timeout(const Duration(seconds: 60));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'analysis': data['analysis'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de l\'analyse',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Generate legal contract template
  /// 
  /// [contractType] - Type of contract
  /// [parameters] - Contract parameters
  /// [token] - User authentication token
  Future<Map<String, dynamic>> generateContract({
    required String contractType,
    required Map<String, dynamic> parameters,
    required String token,
  }) async {
    try {
      final response = await client.post(
        Uri.parse('$baseUrl/ai/contract'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'contract_type': contractType,
          'parameters': parameters,
        }),
      ).timeout(const Duration(seconds: 60));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'contract': data['contract'],
        };
      } else {
        final error = jsonDecode(response.body);
        return {
          'success': false,
          'message': error['message'] ?? 'Erreur lors de la génération',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Erreur de connexion: ${e.toString()}',
      };
    }
  }

  /// Dispose resources
  void dispose() {
    client.close();
  }
}
