class Calculator {
  final int id;
  final String name;
  final String description;
  final String type;
  final String instructions;
  final List<Map<String, dynamic>> inputs;

  Calculator({
    required this.id,
    required this.name,
    required this.description,
    required this.type,
    required this.instructions,
    required this.inputs,
  });

  factory Calculator.fromJson(Map<String, dynamic> json) {
    final inputsRaw = json['inputs'];
    List<Map<String, dynamic>> parsedInputs = [];

    if (inputsRaw is List) {
      parsedInputs = inputsRaw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    }

    return Calculator(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'] ?? '',
      type: json['type'] ?? (json['calculator_type'] ?? ''),
      instructions: json['instructions'] ?? '',
      inputs: parsedInputs,
    );
  }
}
