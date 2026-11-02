import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/main.dart';
import 'package:student_clearance_frontend/models/session.dart';

void main() {
  testWidgets('role selection presents all controlled roles', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));

    expect(find.text('Clearance work, made visible.'), findsOneWidget);
    expect(find.text('Continue as Student'), findsOneWidget);
    expect(find.text('Continue as Clearing office'), findsOneWidget);
    expect(find.text('Continue as Administrator'), findsOneWidget);
  });

  testWidgets('student can enter dashboard and sees empty initiation state', (
    tester,
  ) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));

    await tester.tap(find.text('Continue as Student'));
    await tester.pumpAndSettle();

    expect(find.text('Your clearance'), findsOneWidget);
    expect(find.text('Start this term'), findsOneWidget);
    expect(find.text('Start clearance'), findsOneWidget);
  });

  testWidgets('student receives inline term validation', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    await tester.tap(find.text('Continue as Student'));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField), 'invalid');
    await tester.tap(find.text('Start clearance'));
    await tester.pump();

    expect(find.text('Use a code such as 2026-2027-1.'), findsOneWidget);
  });

  testWidgets('office empty state is explicit', (tester) async {
    await tester.pumpWidget(ClearanceApp(api: FakeClearanceApi()));
    final officeButton = find.text('Continue as Clearing office');
    await tester.ensureVisible(officeButton);
    await tester.tap(officeButton);
    await tester.pumpAndSettle();

    expect(find.text('Library review queue'), findsOneWidget);
    expect(find.text('No assigned tasks'), findsOneWidget);
  });
}

class FakeClearanceApi implements ClearanceApi {
  @override
  Future<DemoSession> createDemoSession(
    String role, {
    String? officeCode,
  }) async {
    return DemoSession(
      token: 'test-token',
      user: DemoUser(
        id: 1,
        name: role == 'student' ? 'Ari Santos' : 'Morgan Reyes',
        role: role,
        officeCode: role == 'office' ? officeCode : null,
        officeName: role == 'office' ? 'Library' : null,
      ),
    );
  }

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
}
