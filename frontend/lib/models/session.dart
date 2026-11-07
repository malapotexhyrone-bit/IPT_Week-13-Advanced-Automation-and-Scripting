class AuthenticatedUser {
  const AuthenticatedUser({
    required this.id,
    required this.name,
    required this.role,
    this.officeCode,
    this.officeName,
  });

  final int id;
  final String name;
  final String role;
  final String? officeCode;
  final String? officeName;

  factory AuthenticatedUser.fromJson(Map<String, dynamic> json) {
    final office = json['office'] as Map<String, dynamic>?;
    return AuthenticatedUser(
      id: json['id'] as int,
      name: json['name'] as String,
      role: json['role'] as String,
      officeCode: office?['code'] as String?,
      officeName: office?['name'] as String?,
    );
  }
}

class AuthenticatedSession {
  const AuthenticatedSession({required this.token, required this.user});

  final String token;
  final AuthenticatedUser user;

  factory AuthenticatedSession.fromJson(Map<String, dynamic> json) {
    return AuthenticatedSession(
      token: json['token'] as String,
      user: AuthenticatedUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }
}
