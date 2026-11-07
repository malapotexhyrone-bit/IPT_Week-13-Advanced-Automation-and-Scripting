import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/main.dart';
import 'package:student_clearance_frontend/models/session.dart';

void main() {
  testWidgets('login screen explains server-owned role and HTTPS boundary', (
    tester,
  ) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));

    expect(
      find.text('Your role begins with a verified identity.'),
      findsOneWidget,
    );
    expect(find.text('Secure sign in'), findsOneWidget);
    expect(find.text('Sign in securely'), findsOneWidget);
    expect(
      find.text('The browser cannot choose or promote its own role.'),
      findsOneWidget,
    );
  });

  testWidgets('login validates missing credentials before calling API', (
    tester,
  ) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    final submit = find.text('Sign in securely');
    await tester.ensureVisible(submit);
    await tester.tap(submit);
    await tester.pump();

    expect(find.text('Enter the account email address.'), findsOneWidget);
    expect(find.text('Enter the account password.'), findsOneWidget);
  });

  testWidgets('invalid credentials use a generic error', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    await _enterCredentials(tester, 'student@example.test', 'wrong');
    final submit = find.text('Sign in securely');
    await tester.ensureVisible(submit);
    await tester.tap(submit);
    await tester.pumpAndSettle();

    expect(find.text('The email or password is incorrect.'), findsOneWidget);
  });

  testWidgets('student login opens only the student dashboard', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    await _signIn(tester, 'student@example.test');

    expect(find.text('Your clearance'), findsOneWidget);
    expect(find.text('Start clearance'), findsOneWidget);
    expect(find.text('Automation review'), findsNothing);
  });

  testWidgets('office login opens the assigned office queue', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    await _signIn(tester, 'office@example.test');

    expect(find.text('Library review queue'), findsOneWidget);
    expect(find.text('No assigned tasks'), findsOneWidget);
  });

  testWidgets('administrator can request the protected exchange', (
    tester,
  ) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    await _signIn(tester, 'admin@example.test');

    expect(find.text('Protected clearance exchange'), findsOneWidget);
    await tester.enterText(find.byKey(const Key('secure-clearance-id')), '1');
    final sendButton = find.text('Send protected record');
    await tester.ensureVisible(sendButton);
    await tester.tap(sendButton);
    await tester.pumpAndSettle();

    expect(
      find.text('Receiver accepted the protected record.'),
      findsOneWidget,
    );
    expect(find.text('Message ID: test-message-id'), findsOneWidget);
  });
}

Future<void> _enterCredentials(
  WidgetTester tester,
  String email,
  String password,
) async {
  await tester.enterText(find.byKey(const Key('login-email')), email);
  await tester.enterText(find.byKey(const Key('login-password')), password);
}

Future<void> _signIn(WidgetTester tester, String email) async {
  await _enterCredentials(tester, email, 'Correct-Horse-42!');
  final submit = find.text('Sign in securely');
  await tester.ensureVisible(submit);
  await tester.tap(submit);
  await tester.pumpAndSettle();
}

class FakeClearanceApi implements ClearanceApi {
  @override
  Future<AuthenticatedSession> login(String email, String password) async {
    if (password == 'wrong') {
      throw const ApiException(
        statusCode: 401,
        code: 'INVALID_CREDENTIALS',
        message: 'The email or password is incorrect.',
      );
    }

    final role = email.startsWith('admin')
        ? 'admin'
        : email.startsWith('office')
        ? 'office'
        : 'student';
    return AuthenticatedSession(
      token: 'test-token',
      user: AuthenticatedUser(
        id: 1,
        name: role == 'student' ? 'Ari Santos' : 'Morgan Reyes',
        role: role,
        officeCode: role == 'office' ? 'LIBRARY' : null,
        officeName: role == 'office' ? 'Library' : null,
      ),
    );
  }

  @override
  Future<void> logout(String token) async {}

  @override
  Future<Map<String, dynamic>?> currentClearance(String token) async => null;

  @override
  Future<Map<String, dynamic>> initiateClearance(
    String token,
    String termCode,
  ) {
    throw UnimplementedError();
  }

  @override
  Future<List<Map<String, dynamic>>> officeTasks(String token) async => [];

  @override
  Future<List<Map<String, dynamic>>> automationFailures(String token) async =>
      [];

  @override
  Future<void> approveTask(String token, int taskId) async {}

  @override
  Future<void> reportDeficiency(
    String token,
    int taskId,
    String description,
  ) async {}

  @override
  Future<void> resolveDeficiency(
    String token,
    int deficiencyId,
    String note,
  ) async {}

  @override
  Future<void> retryAutomation(String token, int deliveryId) async {}

  @override
  Future<Map<String, dynamic>> sendSecureExchange(
    String token,
    int clearanceId,
  ) async => {'message_id': 'test-message-id', 'status': 'accepted'};
}
