/// Valeur d'énumération de l'API, avec son libellé en français.
class Label {
  const Label(this.value, this.label);

  factory Label.from(dynamic json) =>
      json is Map ? Label('${json['value']}', '${json['label']}') : const Label('', '');

  final String value;
  final String label;
}

class Member {
  const Member({required this.id, required this.name, this.region, this.bio, this.photoUrl});

  factory Member.fromJson(Map<String, dynamic> json) => Member(
        id: json['id'] as String,
        name: json['name'] as String? ?? '',
        region: json['region'] as String?,
        bio: json['bio'] as String?,
        photoUrl: json['photo_url'] as String?,
      );

  final String id;
  final String name;
  final String? region;
  final String? bio;
  final String? photoUrl;
}

class Need {
  const Need({required this.id, required this.type, required this.title, this.description, this.amountFcfa, this.notice});

  factory Need.fromJson(Map<String, dynamic> json) => Need(
        id: json['id'] as String,
        type: Label.from(json['type']),
        title: json['title'] as String? ?? '',
        description: json['description'] as String?,
        amountFcfa: json['amount_fcfa'] as int?,
        notice: json['notice'] as String?,
      );

  final String id;
  final Label type;
  final String title;
  final String? description;
  final int? amountFcfa;

  /// « Ceci n'est pas une offre au public », sur les recherches de financement.
  final String? notice;
}

class Project {
  const Project({
    required this.id,
    required this.title,
    required this.summary,
    required this.stage,
    this.region,
    this.sector,
    this.owner,
    this.readinessScore = 0,
    this.isOwner = false,
    this.needs = const [],
  });

  factory Project.fromJson(Map<String, dynamic> json) => Project(
        id: json['id'] as String,
        title: json['title'] as String? ?? '',
        summary: json['summary'] as String? ?? '',
        stage: Label.from(json['stage']),
        region: json['region'] as String?,
        sector: json['sector'] is Map ? (json['sector'] as Map)['name'] as String? : json['sector'] as String?,
        owner: json['owner'] is Map<String, dynamic> ? Member.fromJson(json['owner'] as Map<String, dynamic>) : null,
        readinessScore: json['readiness_score'] as int? ?? 0,
        isOwner: json['is_owner'] as bool? ?? false,
        needs: (json['open_needs'] as List? ?? const []).map((need) => Need.fromJson(need as Map<String, dynamic>)).toList(),
      );

  final String id;
  final String title;
  final String summary;
  final Label stage;
  final String? region;
  final String? sector;
  final Member? owner;
  final int readinessScore;
  final bool isOwner;
  final List<Need> needs;
}

class ContactRequest {
  const ContactRequest({
    required this.id,
    required this.message,
    required this.status,
    required this.createdAt,
    this.sender,
    this.recipient,
    this.projectTitle,
  });

  factory ContactRequest.fromJson(Map<String, dynamic> json) => ContactRequest(
        id: json['id'] as String,
        message: json['message'] as String? ?? '',
        status: Label.from(json['status']),
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
        sender: json['sender'] is Map<String, dynamic> ? Member.fromJson(json['sender'] as Map<String, dynamic>) : null,
        recipient: json['recipient'] is Map<String, dynamic> ? Member.fromJson(json['recipient'] as Map<String, dynamic>) : null,
        projectTitle: json['project'] is Map ? (json['project'] as Map)['title'] as String? : null,
      );

  final String id;
  final String message;
  final Label status;
  final DateTime createdAt;
  final Member? sender;
  final Member? recipient;
  final String? projectTitle;

  bool get isPending => status.value == 'envoyee' || status.value == 'lue';
  bool get allowsMessages => status.value == 'interesse';
}

class Message {
  const Message({required this.id, required this.senderId, required this.createdAt, this.body, this.attachmentName, this.scamAlert = false});

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        id: json['id'] as String,
        senderId: json['sender_id'] as String,
        body: json['body'] as String?,
        attachmentName: json['attachment'] is Map ? (json['attachment'] as Map)['name'] as String? : null,
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
        scamAlert: json['scam_alert'] == true,
      );

  final String id;
  final String senderId;
  final String? body;
  final String? attachmentName;
  final DateTime createdAt;

  /// Le message parle d'argent, d'un code ou de coordonnées bancaires.
  final bool scamAlert;
}

class Journey {
  const Journey({required this.id, required this.title, required this.summary, this.situation, this.stepsCount = 0, this.doneCount = 0, this.steps = const []});

  factory Journey.fromJson(Map<String, dynamic> json) => Journey(
        id: json['id'] as String,
        title: json['title'] as String? ?? '',
        summary: json['summary'] as String? ?? '',
        situation: json['situation'] as String?,
        stepsCount: json['steps_count'] as int? ?? 0,
        doneCount: json['done_steps_count'] as int? ?? 0,
        steps: (json['steps'] as List? ?? const []).map((step) => JourneyStep.fromJson(step as Map<String, dynamic>)).toList(),
      );

  final String id;
  final String title;
  final String summary;
  final String? situation;
  final int stepsCount;
  final int doneCount;
  final List<JourneyStep> steps;
}

class JourneyStep {
  const JourneyStep({required this.id, required this.title, required this.body, this.organisation, this.cost, this.delay, this.status});

  factory JourneyStep.fromJson(Map<String, dynamic> json) => JourneyStep(
        id: json['id'] as String,
        title: json['title'] as String? ?? '',
        body: json['body'] as String? ?? '',
        organisation: json['organisation'] as String?,
        cost: json['cost'] as String?,
        delay: json['delay'] as String?,
        status: json['progress'] is Map ? Label.from((json['progress'] as Map)['status']) : null,
      );

  final String id;
  final String title;
  final String body;
  final String? organisation;
  final String? cost;
  final String? delay;
  final Label? status;

  bool get isDone => status?.value == 'terminee';
}

class AppNotification {
  const AppNotification({required this.id, required this.message, required this.createdAt, this.read = false});

  factory AppNotification.fromJson(Map<String, dynamic> json) => AppNotification(
        id: json['id'] as String,
        message: json['message'] as String? ?? '',
        read: json['read_at'] != null,
        createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      );

  final String id;
  final String message;
  final bool read;
  final DateTime createdAt;
}
