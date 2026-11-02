import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';
import 'package:student_clearance_frontend/widgets/app_shell.dart';
import 'package:student_clearance_frontend/widgets/state_panels.dart';

class StudentDashboard extends StatefulWidget {
  const StudentDashboard({
    super.key,
    required this.api,
    required this.session,
    required this.onSignOut,
  });

  final ClearanceApi api;
  final DemoSession session;
  final VoidCallback onSignOut;

  @override
  State<StudentDashboard> createState() => _StudentDashboardState();
}

class _StudentDashboardState extends State<StudentDashboard> {
  final _termController = TextEditingController(text: '2026-2027-1');
  Map<String, dynamic>? _clearance;
  Object? _error;
  bool _loading = true;
  bool _submitting = false;
  String? _termError;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _termController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      session: widget.session,
      title: 'Your clearance',
      subtitle: 'Track every clearing office and respond when an office reports a deficiency.',
      onSignOut: widget.onSignOut,
      actions: [
        IconButton(
          tooltip: 'Refresh clearance',
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded),
        ),
      ],
      child: _body(),
    );
  }

  Widget _body() {
    if (_loading) return const LoadingPanel();
    if (_error != null) {
      return ErrorPanel(
        error: _error!,
        onRetry: _load,
        onReturnToRoles: widget.onSignOut,
      );
    }
    if (_clearance == null) return _initiationPanel();
    return _clearanceView(_clearance!);
  }

  Widget _initiationPanel() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final content = [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Start this term',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'One task will be created for every active clearing office. Duplicate requests are blocked.',
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 24, height: 20),
              SizedBox(
                width: constraints.maxWidth >= 720 ? 360 : double.infinity,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    TextField(
                      controller: _termController,
                      enabled: !_submitting,
                      decoration: InputDecoration(
                        labelText: 'Academic term code',
                        helperText: 'Use the controlled active term.',
                        errorText: _termError,
                      ),
                    ),
                    const SizedBox(height: 14),
                    FilledButton.icon(
                      onPressed: _submitting ? null : _initiate,
                      icon: const Icon(Icons.play_arrow_rounded),
                      label: Text(
                        _submitting ? 'Starting...' : 'Start clearance',
                      ),
                    ),
                  ],
                ),
              ),
            ];
            return constraints.maxWidth >= 720
                ? Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: content,
                  )
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: content
                        .map((item) => item is Expanded ? item.child : item)
                        .toList(),
                  );
          },
        ),
      ),
    );
  }

  Widget _clearanceView(Map<String, dynamic> clearance) {
    final tasks = (clearance['office_tasks'] as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();
    final approved = tasks.where((task) => task['status'] == 'approved').length;
    final term = clearance['term'] as Map<String, dynamic>;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(22),
            child: Wrap(
              alignment: WrapAlignment.spaceBetween,
              crossAxisAlignment: WrapCrossAlignment.center,
              spacing: 24,
              runSpacing: 16,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      term['name'] as String,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 5),
                    Text('$approved of ${tasks.length} offices approved'),
                  ],
                ),
                StatusLabel(clearance['status'] as String),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        ...tasks.map(_taskCard),
      ],
    );
  }

  Widget _taskCard(Map<String, dynamic> task) {
    final office = task['office'] as Map<String, dynamic>;
    final deficiencies = (task['deficiencies'] as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();
    final open = deficiencies
        .where((item) => item['status'] == 'open')
        .firstOrNull;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFFE7EEE9),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: const Icon(Icons.account_balance_outlined, size: 21),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Text(
                      office['name'] as String,
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                  ),
                  StatusLabel(task['status'] as String),
                ],
              ),
              if (open != null) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF3E8),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Office action required',
                        style: TextStyle(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 6),
                      Text(open['description'] as String),
                      const SizedBox(height: 12),
                      FilledButton.tonalIcon(
                        onPressed: () => _showResolution(open),
                        icon: const Icon(Icons.reply_rounded),
                        label: const Text('Submit resolution'),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await widget.api.currentClearance(widget.session.token);
      if (mounted) setState(() => _clearance = data);
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _initiate() async {
    final term = _termController.text.trim();
    if (!RegExp(r'^\d{4}-\d{4}-\d$').hasMatch(term)) {
      setState(() => _termError = 'Use a code such as 2026-2027-1.');
      return;
    }
    setState(() {
      _submitting = true;
      _termError = null;
      _error = null;
    });
    try {
      final clearance = await widget.api.initiateClearance(
        widget.session.token,
        term,
      );
      if (mounted) setState(() => _clearance = clearance);
    } on ApiException catch (error) {
      if (mounted) {
        setState(() {
          _termError = error.fields['term_code'] is List
              ? (error.fields['term_code'] as List).first.toString()
              : error.message;
        });
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _showResolution(Map<String, dynamic> deficiency) async {
    final controller = TextEditingController();
    String? errorText;
    final submitted = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Resolve deficiency'),
          content: SizedBox(
            width: 480,
            child: TextField(
              controller: controller,
              minLines: 3,
              maxLines: 6,
              decoration: InputDecoration(
                labelText: 'Resolution note',
                helperText: 'Explain the completed corrective action.',
                errorText: errorText,
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () {
                if (controller.text.trim().length < 5) {
                  setDialogState(
                    () => errorText = 'Enter at least 5 characters.',
                  );
                } else {
                  Navigator.pop(dialogContext, true);
                }
              },
              child: const Text('Submit resolution'),
            ),
          ],
        ),
      ),
    );
    if (submitted == true) {
      try {
        await widget.api.resolveDeficiency(
          widget.session.token,
          deficiency['id'] as int,
          controller.text.trim(),
        );
        await _load();
      } catch (error) {
        if (mounted) setState(() => _error = error);
      }
    }
    controller.dispose();
  }
}
