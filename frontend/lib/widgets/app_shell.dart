import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/models/session.dart';

class AppShell extends StatelessWidget {
  const AppShell({
    super.key,
    required this.session,
    required this.title,
    required this.subtitle,
    required this.onSignOut,
    required this.child,
    this.actions = const [],
  });

  final DemoSession session;
  final String title;
  final String subtitle;
  final VoidCallback onSignOut;
  final Widget child;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 68,
        backgroundColor: Theme.of(context).colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        title: const Row(
          children: [
            _BrandMark(),
            SizedBox(width: 12),
            Text(
              'Clearance Desk',
              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
            ),
          ],
        ),
        actions: [
          ...actions,
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: IconButton(
              tooltip: 'Sign out of demo',
              onPressed: onSignOut,
              icon: const Icon(Icons.logout_rounded),
            ),
          ),
        ],
      ),
      body: SafeArea(
        top: false,
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 1180),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(20, 28, 20, 48),
              children: [
                Wrap(
                  alignment: WrapAlignment.spaceBetween,
                  crossAxisAlignment: WrapCrossAlignment.end,
                  spacing: 24,
                  runSpacing: 16,
                  children: [
                    ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 680),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            style: Theme.of(context).textTheme.headlineMedium,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            subtitle,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ],
                      ),
                    ),
                    _Identity(user: session.user),
                  ],
                ),
                const SizedBox(height: 28),
                child,
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _BrandMark extends StatelessWidget {
  const _BrandMark();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 36,
      height: 36,
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary,
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Icon(
        Icons.fact_check_outlined,
        color: Colors.white,
        size: 21,
      ),
    );
  }
}

class _Identity extends StatelessWidget {
  const _Identity({required this.user});

  final DemoUser user;

  @override
  Widget build(BuildContext context) {
    final detail = user.officeName ?? _roleLabel(user.role);
    return Semantics(
      label: 'Signed in as ${user.name}, $detail',
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFFE7EEE9),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.person_outline_rounded, size: 20),
            const SizedBox(width: 8),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  user.name,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
                Text(detail, style: Theme.of(context).textTheme.bodySmall),
              ],
            ),
          ],
        ),
      ),
    );
  }

  String _roleLabel(String role) => switch (role) {
    'student' => 'Student',
    'office' => 'Office personnel',
    'admin' => 'Administrator',
    _ => role,
  };
}
