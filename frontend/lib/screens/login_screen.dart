import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.api, required this.onSignedIn});

  final ClearanceApi api;
  final ValueChanged<AuthenticatedSession> onSignedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _busy = false;
  bool _obscurePassword = true;
  Object? _error;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 1100),
            child: LayoutBuilder(
              builder: (context, constraints) {
                final wide = constraints.maxWidth >= 820;
                final intro = const _SecurityIntro();
                final form = _LoginCard(
                  formKey: _formKey,
                  emailController: _emailController,
                  passwordController: _passwordController,
                  busy: _busy,
                  obscurePassword: _obscurePassword,
                  error: _error,
                  onTogglePassword: () =>
                      setState(() => _obscurePassword = !_obscurePassword),
                  onSubmit: _login,
                );
                return SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: wide
                      ? Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            const Expanded(flex: 5, child: _SecurityIntro()),
                            const SizedBox(width: 64),
                            Expanded(flex: 4, child: form),
                          ],
                        )
                      : Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [intro, const SizedBox(height: 32), form],
                        ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _login() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final session = await widget.api.login(
        _emailController.text,
        _passwordController.text,
      );
      _passwordController.clear();
      if (mounted) widget.onSignedIn(session);
    } catch (error) {
      _passwordController.clear();
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _SecurityIntro extends StatelessWidget {
  const _SecurityIntro();

  @override
  Widget build(BuildContext context) {
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
              Icons.lock_outline_rounded,
              color: Colors.white,
              size: 27,
            ),
          ),
          const SizedBox(height: 28),
          Text(
            'Your role begins with a verified identity.',
            style: Theme.of(context).textTheme.displaySmall,
          ),
          const SizedBox(height: 18),
          Text(
            'Sign in with a controlled fictional account. Laravel verifies the password hash and chooses the student, office, or administrator workspace on the server.',
            style: Theme.of(context).textTheme.bodyLarge,
          ),
          const SizedBox(height: 24),
          const _BoundaryRow(
            icon: Icons.password_rounded,
            text: 'Passwords are never stored or returned as readable text.',
          ),
          const SizedBox(height: 12),
          const _BoundaryRow(
            icon: Icons.admin_panel_settings_outlined,
            text: 'The browser cannot choose or promote its own role.',
          ),
          const SizedBox(height: 12),
          const _BoundaryRow(
            icon: Icons.https_outlined,
            text:
                'The local demonstration requires the trusted HTTPS endpoint.',
          ),
        ],
      ),
    );
  }
}

class _BoundaryRow extends StatelessWidget {
  const _BoundaryRow({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 21, color: Theme.of(context).colorScheme.primary),
        const SizedBox(width: 11),
        Expanded(child: Text(text)),
      ],
    );
  }
}

class _LoginCard extends StatelessWidget {
  const _LoginCard({
    required this.formKey,
    required this.emailController,
    required this.passwordController,
    required this.busy,
    required this.obscurePassword,
    required this.error,
    required this.onTogglePassword,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController emailController;
  final TextEditingController passwordController;
  final bool busy;
  final bool obscurePassword;
  final Object? error;
  final VoidCallback onTogglePassword;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    final errorMessage = error is ApiException
        ? (error as ApiException).message
        : error == null
        ? null
        : 'Sign-in could not be completed.';
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(26),
        child: Form(
          key: formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Secure sign in',
                style: Theme.of(context).textTheme.titleLarge,
              ),
              const SizedBox(height: 7),
              const Text(
                'Use only the fictional account prepared for this demonstration.',
              ),
              const SizedBox(height: 24),
              TextFormField(
                key: const Key('login-email'),
                controller: emailController,
                enabled: !busy,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.username],
                decoration: const InputDecoration(
                  labelText: 'Email address',
                  prefixIcon: Icon(Icons.alternate_email_rounded),
                ),
                validator: (value) {
                  final email = value?.trim() ?? '';
                  if (email.isEmpty || !email.contains('@')) {
                    return 'Enter the account email address.';
                  }
                  return null;
                },
                onFieldSubmitted: (_) {
                  if (!busy) onSubmit();
                },
              ),
              const SizedBox(height: 16),
              TextFormField(
                key: const Key('login-password'),
                controller: passwordController,
                enabled: !busy,
                obscureText: obscurePassword,
                autofillHints: const [AutofillHints.password],
                decoration: InputDecoration(
                  labelText: 'Password',
                  prefixIcon: const Icon(Icons.key_rounded),
                  suffixIcon: IconButton(
                    tooltip: obscurePassword
                        ? 'Show password'
                        : 'Hide password',
                    onPressed: busy ? null : onTogglePassword,
                    icon: Icon(
                      obscurePassword
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                    ),
                  ),
                ),
                validator: (value) => (value?.isEmpty ?? true)
                    ? 'Enter the account password.'
                    : null,
                onFieldSubmitted: (_) {
                  if (!busy) onSubmit();
                },
              ),
              if (errorMessage != null) ...[
                const SizedBox(height: 18),
                Semantics(
                  liveRegion: true,
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.errorContainer,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Text(errorMessage),
                  ),
                ),
              ],
              const SizedBox(height: 22),
              FilledButton.icon(
                onPressed: busy ? null : onSubmit,
                icon: const Icon(Icons.login_rounded),
                label: Text(busy ? 'Verifying...' : 'Sign in securely'),
              ),
              const SizedBox(height: 12),
              Text(
                'Five failed requests per minute are allowed before temporary throttling.',
                style: Theme.of(context).textTheme.bodySmall,
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
