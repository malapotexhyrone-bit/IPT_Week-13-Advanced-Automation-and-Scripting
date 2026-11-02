class DemoUser {
  const DemoUser({
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

  factory DemoUser.fromJson(Map<String, dynamic> json) {
    final office = json['office'] as Map<String, dynamic>?;
    return DemoUser(
      id: json['id'] as int,
      name: json['name'] as String,
      role: json['role'] as String,
      officeCode: office?['code'] as String?,
      officeName: office?['name'] as String?,
    );
  }
}

class DemoSession {
  const DemoSession({required this.token, required this.user});

  final String token;
  final DemoUser user;

  factory DemoSession.fromJson(Map<String, dynamic> json) {
    return DemoSession(
      token: json['token'] as String,
      user: DemoUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }
}
