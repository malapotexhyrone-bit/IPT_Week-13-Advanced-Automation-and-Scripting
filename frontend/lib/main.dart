import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';
import 'package:student_clearance_frontend/screens/admin_dashboard.dart';
import 'package:student_clearance_frontend/screens/office_dashboard.dart';
import 'package:student_clearance_frontend/screens/role_selection_screen.dart';
import 'package:student_clearance_frontend/screens/student_dashboard.dart';

void main() {
  runApp(const ClearanceApp());
}

class ClearanceApp extends StatefulWidget {
  const ClearanceApp({super.key, this.api});

  final ClearanceApi? api;

  @override
  State<ClearanceApp> createState() => _ClearanceAppState();
}

class _ClearanceAppState extends State<ClearanceApp> {
  late final ClearanceApi _api = widget.api ?? HttpClearanceApi();
  DemoSession? _session;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Student Clearance',
      debugShowCheckedModeBanner: false,
      theme: _theme(),
      home: _session == null
          ? RoleSelectionScreen(api: _api, onSignedIn: _setSession)
          : _dashboard(_session!),
    );
  }

  Widget _dashboard(DemoSession session) {
    return switch (session.user.role) {
      'student' => StudentDashboard(
        api: _api,
        session: session,
        onSignOut: _signOut,
      ),
      'office' => OfficeDashboard(
        api: _api,
        session: session,
        onSignOut: _signOut,
      ),
      'admin' => AdminDashboard(
        api: _api,
        session: session,
        onSignOut: _signOut,
      ),
      _ => RoleSelectionScreen(api: _api, onSignedIn: _setSession),
    };
  }

  void _setSession(DemoSession session) => setState(() => _session = session);

  void _signOut() => setState(() => _session = null);

  ThemeData _theme() {
    const ink = Color(0xFF1D2925);
    const accent = Color(0xFF276749);
    const surface = Color(0xFFF5F7F4);
    final scheme = ColorScheme.fromSeed(
      seedColor: accent,
      brightness: Brightness.light,
      surface: surface,
    );

    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme.copyWith(
        primary: accent,
        onPrimary: Colors.white,
        onSurface: ink,
        outline: const Color(0xFF718079),
      ),
      scaffoldBackgroundColor: surface,
      textTheme: const TextTheme(
        displaySmall: TextStyle(
          color: ink,
          fontSize: 44,
          height: 1.05,
          fontWeight: FontWeight.w700,
          letterSpacing: -1.2,
        ),
        headlineMedium: TextStyle(
          color: ink,
          fontSize: 28,
          height: 1.15,
          fontWeight: FontWeight.w700,
          letterSpacing: -0.4,
        ),
        titleLarge: TextStyle(
          color: ink,
          fontSize: 20,
          fontWeight: FontWeight.w700,
        ),
        bodyLarge: TextStyle(color: Color(0xFF3E4C46), height: 1.45),
        bodyMedium: TextStyle(color: Color(0xFF52615B), height: 1.4),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFC5CEC9)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: Color(0xFFC5CEC9)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: accent, width: 2),
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: Color(0xFFD9E0DC)),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
          textStyle: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}
