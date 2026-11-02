import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';
import 'package:student_clearance_frontend/widgets/app_shell.dart';
import 'package:student_clearance_frontend/widgets/state_panels.dart';

class AdminDashboard extends StatefulWidget {
  const AdminDashboard({
    super.key,
    required this.api,
    required this.session,
    required this.onSignOut,
  });

  final ClearanceApi api;
  final DemoSession session;
  final VoidCallback onSignOut;

  @override
  State<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends State<AdminDashboard> {
  List<Map<String, dynamic>> _failures = const [];
  Object? _error;
  bool _loading = true;
  int? _busyDelivery;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      session: widget.session,
      title: 'Automation review',
      subtitle: 'Inspect bounded failures and retry only when the recorded outcome is eligible.',
      onSignOut: widget.onSignOut,
      actions: [
        IconButton(
          tooltip: 'Refresh failures',
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded),
        ),
      ],
      child: _body(),
    );
  }

  Widget _body() {
    if (_loading) {
      return const LoadingPanel(label: 'Loading automation failures');
    }
    if (_error != null) {
      return ErrorPanel(
        error: _error!,
        onRetry: _load,
        onReturnToRoles: widget.onSignOut,
      );
    }
    if (_failures.isEmpty) {
      return const EmptyPanel(
        icon: Icons.verified_outlined,
        title: 'No automation failures',
        message: 'No delivery currently requires administrator action.',
      );
    }
    return Column(children: _failures.map(_failureCard).toList());
  }

  Widget _failureCard(Map<String, dynamic> failure) {
    final id = failure['id'] as int;
    final retryAllowed = failure['retry_allowed'] as bool? ?? false;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Wrap(
                alignment: WrapAlignment.spaceBetween,
                crossAxisAlignment: WrapCrossAlignment.center,
                spacing: 16,
                runSpacing: 10,
                children: [
                  Text(
                    _typeLabel(failure['automation_type'] as String),
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const StatusLabel('human_action_required'),
                ],
              ),
              const SizedBox(height: 14),
              Text('Safe error code: ${failure['error_code']}'),
              const SizedBox(height: 5),
              Text('Attempts: ${failure['attempt_count']}'),
              const SizedBox(height: 5),
              SelectableText('Reference: ${failure['correlation_id']}'),
              const SizedBox(height: 12),
              Text(
                failure['next_step'] as String? ??
                    'Inspect the controlled dependency.',
              ),
              const SizedBox(height: 18),
              Align(
                alignment: Alignment.centerLeft,
                child: FilledButton.icon(
                  onPressed: !retryAllowed || _busyDelivery == id
                      ? null
                      : () => _retry(id),
                  icon: const Icon(Icons.replay_rounded),
                  label: Text(
                    _busyDelivery == id
                        ? 'Requesting...'
                        : 'Retry eligible item',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _typeLabel(String type) => switch (type) {
    'office_task_reminder' => 'Office reminder',
    'stalled_task_escalation' => 'Stalled task escalation',
    'clearance_completion' => 'Completion notification',
    _ => 'Automation delivery',
  };

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final failures = await widget.api.automationFailures(
        widget.session.token,
      );
      if (mounted) setState(() => _failures = failures);
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _retry(int deliveryId) async {
    setState(() => _busyDelivery = deliveryId);
    try {
      await widget.api.retryAutomation(widget.session.token, deliveryId);
      await _load();
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busyDelivery = null);
    }
  }
}
