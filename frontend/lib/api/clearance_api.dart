import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:student_clearance_frontend/models/session.dart';

class ApiException implements Exception {
  const ApiException({
    required this.statusCode,
    required this.code,
    required this.message,
    this.correlationId,
    this.fields = const {},
  });

  final int statusCode;
  final String code;
  final String message;
  final String? correlationId;
  final Map<String, dynamic> fields;

  bool get isUnauthorized => statusCode == 401 || statusCode == 403;
}

abstract class ClearanceApi {
  Future<AuthenticatedSession> login(String email, String password);

  Future<void> logout(String token);

  Future<Map<String, dynamic>?> currentClearance(String token);

  Future<Map<String, dynamic>> initiateClearance(String token, String termCode);

  Future<List<Map<String, dynamic>>> officeTasks(String token);

  Future<void> reportDeficiency(String token, int taskId, String description);

  Future<void> resolveDeficiency(String token, int deficiencyId, String note);

  Future<void> approveTask(String token, int taskId);

  Future<List<Map<String, dynamic>>> automationFailures(String token);

  Future<void> retryAutomation(String token, int deliveryId);

  Future<Map<String, dynamic>> sendSecureExchange(
    String token,
    int clearanceId,
  );
}

class HttpClearanceApi implements ClearanceApi {
  HttpClearanceApi({http.Client? client, String? baseUrl})
    : _client = client ?? http.Client(),
      baseUrl =
          baseUrl ??
          const String.fromEnvironment(
            'API_BASE_URL',
            defaultValue: 'https://localhost:8443/api/v1',
          );

  final http.Client _client;
  final String baseUrl;

  @override
  Future<AuthenticatedSession> login(String email, String password) async {
    final response = await _send(
      'POST',
      '/auth/login',
      body: {'email': email.trim(), 'password': password},
    );
    return AuthenticatedSession.fromJson(
      response['data'] as Map<String, dynamic>,
    );
  }

  @override
  Future<void> logout(String token) async {
    await _send('POST', '/auth/logout', token: token);
  }

  @override
  Future<Map<String, dynamic>?> currentClearance(String token) async {
    final response = await _send('GET', '/clearances/current', token: token);
    return response['data'] as Map<String, dynamic>?;
  }

  @override
  Future<Map<String, dynamic>> initiateClearance(
    String token,
    String termCode,
  ) async {
    final response = await _send(
      'POST',
      '/clearances',
      token: token,
      body: {'term_code': termCode},
    );
    return response['data'] as Map<String, dynamic>;
  }

  @override
  Future<List<Map<String, dynamic>>> officeTasks(String token) async {
    final response = await _send('GET', '/office-tasks', token: token);
    return _mapList(response['data']);
  }

  @override
  Future<void> reportDeficiency(
    String token,
    int taskId,
    String description,
  ) async {
    await _send(
      'POST',
      '/office-tasks/$taskId/deficiencies',
      token: token,
      body: {'description': description},
    );
  }

  @override
  Future<void> resolveDeficiency(
    String token,
    int deficiencyId,
    String note,
  ) async {
    await _send(
      'POST',
      '/deficiencies/$deficiencyId/resolution',
      token: token,
      body: {'resolution_note': note},
    );
  }

  @override
  Future<void> approveTask(String token, int taskId) async {
    await _send('POST', '/office-tasks/$taskId/approve', token: token);
  }

  @override
  Future<List<Map<String, dynamic>>> automationFailures(String token) async {
    final response = await _send('GET', '/automation/failures', token: token);
    return _mapList(response['data']);
  }

  @override
  Future<void> retryAutomation(String token, int deliveryId) async {
    await _send(
      'POST',
      '/automation/deliveries/$deliveryId/retry',
      token: token,
    );
  }

  @override
  Future<Map<String, dynamic>> sendSecureExchange(
    String token,
    int clearanceId,
  ) async {
    final response = await _send(
      'POST',
      '/secure-exchange/send',
      token: token,
      body: {'clearance_id': clearanceId},
    );
    return response['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    String? token,
    Map<String, dynamic>? body,
  }) async {
    final uri = Uri.parse('$baseUrl$path');
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
    if (token != null) headers['Authorization'] = 'Bearer $token';

    late http.Response response;
    try {
      response = switch (method) {
        'GET' => await _client.get(uri, headers: headers),
        'POST' => await _client.post(
          uri,
          headers: headers,
          body: body == null ? null : jsonEncode(body),
        ),
        _ => throw ArgumentError('Unsupported method: $method'),
      };
    } on http.ClientException {
      throw const ApiException(
        statusCode: 0,
        code: 'NETWORK_UNAVAILABLE',
        message: 'The trusted local HTTPS API could not be reached. Check Laravel and the localhost certificate.',
      );
    }

    final decoded = response.body.isEmpty
        ? <String, dynamic>{}
        : jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    final error = decoded['error'] as Map<String, dynamic>?;
    throw ApiException(
      statusCode: response.statusCode,
      code: error?['code'] as String? ?? 'REQUEST_FAILED',
      message:
          error?['message'] as String? ?? 'The request could not be completed.',
      correlationId: error?['correlation_id'] as String?,
      fields: (error?['fields'] as Map<String, dynamic>?) ?? const {},
    );
  }

  List<Map<String, dynamic>> _mapList(dynamic value) {
    return (value as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();
  }
}
