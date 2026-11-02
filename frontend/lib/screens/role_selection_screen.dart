import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';

class RoleSelectionScreen extends StatefulWidget {
  const RoleSelectionScreen({
    super.key,
    required this.api,
    required this.onSignedIn,
  });

  final ClearanceApi api;
  final ValueChanged<DemoSession> onSignedIn;

  @override
  State<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends State<RoleSelectionScreen> {
  static const _offices = {
    'REGISTRAR': 'Registrar',
    'LIBRARY': 'Library',
    'ACCOUNTING': 'Accounting',
    'STUDENT_AFFAIRS': 'Student Affairs',
  };

  String _officeCode = 'LIBRARY';
  String? _busyRole;
  Object? _error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 1180),
            child: LayoutBuilder(
              builder: (context, constraints) {
                final wide = constraints.maxWidth >= 860;
                final intro = _IntroPanel(error: _error);
                final choices = _roleChoices();
                return SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: wide
                      ? Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(flex: 5, child: intro),
                            const SizedBox(width: 56),
                            Expanded(flex: 6, child: choices),
                          ],
                        )
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            intro,
                            const SizedBox(height: 32),
                            choices,
                          ],
                        ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  Widget _roleChoices() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _RoleCard(
          icon: Icons.school_outlined,
          title: 'Student',
          description: 'Start a clearance, follow office tasks, and resolve a reported deficiency.',
          loading: _busyRole == 'student',
          onPressed: () => _connect('student'),
        ),
        const SizedBox(height: 14),
        _RoleCard(
          icon: Icons.approval_outlined,
          title: 'Clearing office',
          description: 'Review only the tasks assigned to the selected office.',
          loading: _busyRole == 'office',
          footer: DropdownButtonFormField<String>(
            initialValue: _officeCode,
            decoration: const InputDecoration(labelText: 'Assigned office'),
            items: _offices.entries
                .map(
                  (item) => DropdownMenuItem(
                    value: item.key,
                    child: Text(item.value),
                  ),
                )
                .toList(),
            onChanged: _busyRole == null
                ? (value) => setState(() => _officeCode = value ?? _officeCode)
                : null,
          ),
          onPressed: () => _connect('office', officeCode: _officeCode),
        ),
        const SizedBox(height: 14),
        _RoleCard(
          icon: Icons.admin_panel_settings_outlined,
          title: 'Administrator',
          description: 'Inspect automation failures and retry only eligible recovery items.',
          loading: _busyRole == 'admin',
          onPressed: () => _connect('admin'),
        ),
      ],
    );
  }

  Future<void> _connect(String role, {String? officeCode}) async {
    setState(() {
      _busyRole = role;
      _error = null;
    });
    try {
      final session = await widget.api.createDemoSession(
        role,
        officeCode: officeCode,
      );
      if (mounted) widget.onSignedIn(session);
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busyRole = null);
    }
  }
}

class _IntroPanel extends StatelessWidget {
  const _IntroPanel({required this.error});

  final Object? error;

  @override
  Widget build(BuildContext context) {
    final message = error is ApiException
        ? (error as ApiException).message
        : error == null
        ? null
        : 'The controlled demo session could not be started.';
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.primary,
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.fact_check_outlined,
              color: Colors.white,
              size: 28,
            ),
          ),
          const SizedBox(height: 28),
          Text(
            'Clearance work, made visible.',
            style: Theme.of(context).textTheme.displaySmall,
          ),
          const SizedBox(height: 18),
          Text(
            'Use controlled fictional accounts to demonstrate the student, office, and automation journeys.',
            style: Theme.of(context).textTheme.bodyLarge,
          ),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFE7EEE9),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.science_outlined, size: 22),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Local demonstration only. No university system or real student record is connected.',
                  ),
                ),
              ],
            ),
          ),
          if (message != null) ...[
            const SizedBox(height: 18),
            Semantics(
              liveRegion: true,
              child: Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: Theme.of(context).colorScheme.errorContainer,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  children: [
                    Icon(
                      Icons.error_outline,
                      color: Theme.of(context).colorScheme.error,
                    ),
                    const SizedBox(width: 10),
                    Expanded(child: Text(message)),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _RoleCard extends StatelessWidget {
  const _RoleCard({
    required this.icon,
    required this.title,
    required this.description,
    required this.loading,
    required this.onPressed,
    this.footer,
  });

  final IconData icon;
  final String title;
  final String description;
  final bool loading;
  final VoidCallback onPressed;
  final Widget? footer;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  icon,
                  color: Theme.of(context).colorScheme.primary,
                  size: 27,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 6),
                      Text(description),
                    ],
                  ),
                ),
              ],
            ),
            if (footer != null) ...[const SizedBox(height: 18), footer!],
            const SizedBox(height: 18),
            FilledButton(
              onPressed: loading ? null : onPressed,
              child: Text(loading ? 'Connecting...' : 'Continue as $title'),
            ),
          ],
        ),
      ),
    );
  }
}
