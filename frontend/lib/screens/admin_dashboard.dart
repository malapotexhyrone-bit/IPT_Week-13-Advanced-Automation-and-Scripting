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
  final AuthenticatedSession session;
  final VoidCallback onSignOut;

  @override
  State<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends State<AdminDashboard> {
  final _clearanceIdController = TextEditingController();
  List<Map<String, dynamic>> _failures = const [];
  Object? _error;
  Object? _exchangeError;
  Map<String, dynamic>? _exchangeResult;
  bool _loading = true;
  bool _sendingExchange = false;
  int? _busyDelivery;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _clearanceIdController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      session: widget.session,
      title: 'Security and automation review',
      subtitle: 'Send a minimized protected clearance record or inspect bounded automation failures.',
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _secureExchangeCard(),
        const SizedBox(height: 22),
        Text(
          'Automation failures',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 14),
        _automationBody(),
      ],
    );
  }

  Widget _automationBody() {
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

  Widget _secureExchangeCard() {
    final result = _exchangeResult;
    final errorMessage = _exchangeError is ApiException
        ? (_exchangeError as ApiException).message
        : _exchangeError == null
        ? null
        : 'The protected exchange could not be completed.';

    return Card(
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
                  'Protected clearance exchange',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const StatusLabel('administrator_only'),
              ],
            ),
            const SizedBox(height: 10),
            const Text(
              'The server loads the selected clearance, minimizes its fields, encrypts it with AES-256-GCM, and sends it through validated HTTPS.',
            ),
            const SizedBox(height: 18),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 320),
              child: TextField(
                key: const Key('secure-clearance-id'),
                controller: _clearanceIdController,
                enabled: !_sendingExchange,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Clearance database ID',
                  helperText: 'Use a fictional local clearance record.',
                ),
              ),
            ),
            const SizedBox(height: 16),
            Align(
              alignment: Alignment.centerLeft,
              child: FilledButton.icon(
                onPressed: _sendingExchange ? null : _sendSecureExchange,
                icon: const Icon(Icons.enhanced_encryption_outlined),
                label: Text(
                  _sendingExchange
                      ? 'Protecting and sending...'
                      : 'Send protected record',
                ),
              ),
            ),
            if (errorMessage != null) ...[
              const SizedBox(height: 16),
              Semantics(
                liveRegion: true,
                child: Text(
                  errorMessage,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            ],
            if (result != null) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFE7EEE9),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Receiver accepted the protected record.',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 5),
                    SelectableText('Message ID: ${result['message_id']}'),
                    Text('Status: ${result['status'] ?? 'accepted'}'),
                  ],
                ),
              ),
            ],
          ],
        ),
      ),
    );
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

  Future<void> _sendSecureExchange() async {
    final clearanceId = int.tryParse(_clearanceIdController.text.trim());
    if (clearanceId == null || clearanceId < 1) {
      setState(() {
        _exchangeResult = null;
        _exchangeError = const ApiException(
          statusCode: 422,
          code: 'INVALID_CLEARANCE_ID',
          message: 'Enter a valid fictional clearance database ID.',
        );
      });
      return;
    }

    setState(() {
      _sendingExchange = true;
      _exchangeError = null;
      _exchangeResult = null;
    });
    try {
      final result = await widget.api.sendSecureExchange(
        widget.session.token,
        clearanceId,
      );
      if (mounted) setState(() => _exchangeResult = result);
    } catch (error) {
      if (mounted) setState(() => _exchangeError = error);
    } finally {
      if (mounted) setState(() => _sendingExchange = false);
    }
  }
}
