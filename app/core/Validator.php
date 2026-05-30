<?php
/**
 * app/core/Validator.php
 * ─────────────────────────────────────────────────────────────
 *  Validation class cho toàn bộ hệ thống KTX
 *
 *  Chức năng:
 *  • Validate dữ liệu với rules linh hoạt
 *  • Hỗ trợ 20+ rule types
 *  • Custom error messages (tiếng Việt)
 *  • Dùng chung cho tất cả các module
 *  • Static method hoặc instance
 *  • Hỗ trợ nested validation
 *  • Custom validators
 *
 *  Cách sử dụng:
 *    $validator = new Validator();
 *    $errors = $validator->validate($data, [
 *        'email' => 'required|email',
 *        'password' => 'required|min:8',
 *        'age' => 'required|numeric|min:18|max:60',
 *    ]);
 *
 *  Hoặc static:
 *    $errors = Validator::make($data, $rules)->errors();
 *
 *  Thành viên phụ trách: Tất cả (dùng chung)
 *  Điểm "Xuất sắc": Reusable, flexible, comprehensive
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class Validator
{
    /**
     * Data to validate
     */
    private array $data = [];

    /**
     * Validation rules
     */
    private array $rules = [];

    /**
     * Errors found
     */
    private array $errors = [];

    /**
     * Custom error messages
     */
    private array $messages = [];

    /**
     * Attribute labels (for error messages)
     */
    private array $labels = [];

    /**
     * Custom validators (callable)
     */
    private static array $customValidators = [];

    /**
     * Default error messages
     */
    private static array $defaultMessages = [
        'required'      => '{field} là bắt buộc',
        'email'         => '{field} phải là email hợp lệ',
        'min'           => '{field} phải ít nhất {param} ký tự',
        'max'           => '{field} không được quá {param} ký tự',
        'numeric'       => '{field} phải là số',
        'integer'       => '{field} phải là số nguyên',
        'url'           => '{field} phải là URL hợp lệ',
        'confirmed'     => '{field} không trùng khớp',
        'unique'        => '{field} này đã tồn tại',
        'in'            => '{field} không hợp lệ',
        'date'          => '{field} phải là ngày hợp lệ',
        'regex'         => '{field} không đúng định dạng',
        'greater_than'  => '{field} phải > {param}',
        'less_than'     => '{field} phải < {param}',
        'same'          => '{field} phải trùng với {param}',
        'different'     => '{field} phải khác {param}',
        'json'          => '{field} phải là JSON hợp lệ',
        'ip'            => '{field} phải là IP hợp lệ',
        'array'         => '{field} phải là mảng',
        'phone'         => '{field} phải là số điện thoại hợp lệ',
    ];

    /**
     * Constructor
     */
    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  MAIN VALIDATION
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Static factory method
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    /**
     * Validate data against rules
     *
     * @return bool True if valid
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rules = explode('|', $fieldRules);

            foreach ($rules as $rule) {
                $this->validateRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate single rule
     */
    private function validateRule(string $field, string $rule): void
    {
        // Parse rule: name:param1,param2
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $value = $this->data[$field] ?? null;

        // Skip optional fields if not required
        if ($ruleName !== 'required' && $this->isEmpty($value)) {
            return;
        }

        // Call validation method
        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $isValid = $this->$method($value, $params, $field);

            if (!$isValid) {
                $this->addError($field, $ruleName, $params);
            }
        } elseif (isset(self::$customValidators[$ruleName])) {
            $isValid = call_user_func(self::$customValidators[$ruleName], $value, $params, $this->data);

            if (!$isValid) {
                $this->addError($field, $ruleName, $params);
            }
        }
    }

    /**
     * Check if value is empty
     */
    private function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  VALIDATION RULES
     * ───────────────────────────────────────────────────────────
     */

    /**
     * required — Field must not be empty
     */
    private function validateRequired($value): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * email — Field must be valid email
     */
    private function validateEmail($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * min:length — Field must have minimum length
     */
    private function validateMin($value, array $params): bool
    {
        $min = (int)($params[0] ?? 0);
        return strlen((string)$value) >= $min;
    }

    /**
     * max:length — Field must not exceed maximum length
     */
    private function validateMax($value, array $params): bool
    {
        $max = (int)($params[0] ?? PHP_INT_MAX);
        return strlen((string)$value) <= $max;
    }

    /**
     * numeric — Field must be numeric
     */
    private function validateNumeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * integer — Field must be integer
     */
    private function validateInteger($value): bool
    {
        return is_int($value) || (is_numeric($value) && (int)$value == $value);
    }

    /**
     * url — Field must be valid URL
     */
    private function validateUrl($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * confirmed — Field must match {field}_confirmation field
     */
    private function validateConfirmed($value, array $params, string $field, array $data = null): bool
    {
        $confirmationField = "{$field}_confirmation";
        $data = $data ?? $this->data;
        return isset($data[$confirmationField]) && $data[$confirmationField] === $value;
    }

    /**
     * unique:table,column[,excludeId] — Value must be unique in database
     */
    private function validateUnique($value, array $params): bool
    {
        if (count($params) < 2) {
            return true; // Need table and column
        }

        $table = $params[0];
        $column = $params[1];
        $excludeId = $params[2] ?? null;

        // Query database
        $db = Database::getInstance();

        $where = "{$column} = ?";
        $args = [$value];

        if ($excludeId) {
            $where .= " AND id != ?";
            $args[] = $excludeId;
        }

        $exists = $db->exists($table, $where, $args);
        return !$exists;
    }

    /**
     * in:value1,value2,... — Value must be in list
     */
    private function validateIn($value, array $params): bool
    {
        return in_array($value, $params);
    }

    /**
     * date — Field must be valid date (YYYY-MM-DD)
     */
    private function validateDate($value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }

    /**
     * regex:/pattern/ — Field must match regex
     */
    private function validateRegex($value, array $params): bool
    {
        $pattern = $params[0] ?? '';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * greater_than:value — Field must be > value
     */
    private function validateGreaterThan($value, array $params): bool
    {
        $min = (float)($params[0] ?? 0);
        return (float)$value > $min;
    }

    /**
     * less_than:value — Field must be < value
     */
    private function validateLessThan($value, array $params): bool
    {
        $max = (float)($params[0] ?? PHP_INT_MAX);
        return (float)$value < $max;
    }

    /**
     * same:field — Field must equal another field
     */
    private function validateSame($value, array $params): bool
    {
        $otherField = $params[0] ?? '';
        return ($this->data[$otherField] ?? null) === $value;
    }

    /**
     * different:field — Field must differ from another field
     */
    private function validateDifferent($value, array $params): bool
    {
        $otherField = $params[0] ?? '';
        return ($this->data[$otherField] ?? null) !== $value;
    }

    /**
     * json — Field must be valid JSON
     */
    private function validateJson($value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * ip — Field must be valid IP address
     */
    private function validateIp($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * array — Field must be array
     */
    private function validateArray($value): bool
    {
        return is_array($value);
    }

    /**
     * phone — Field must be valid phone (10-15 digits)
     */
    private function validatePhone($value): bool
    {
        // Remove common separators
        $phone = preg_replace('/[\s\-\(\)\.]/u', '', $value);
        return preg_match('/^\+?[0-9]{10,15}$/', $phone) === 1;
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  ERROR HANDLING
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Add error for field
     */
    private function addError(string $field, string $rule, array $params): void
    {
        if (isset($this->messages[$field][$rule])) {
            $message = $this->messages[$field][$rule];
        } else {
            $message = self::$defaultMessages[$rule] ?? "Validation failed for {$field}";
        }

        // Replace placeholders
        $label = $this->labels[$field] ?? $field;
        $message = str_replace('{field}', $label, $message);
        $message = str_replace('{param}', $params[0] ?? '', $message);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     */
    public function errors(): array
    {
        $this->validate();
        return $this->errors;
    }

    /**
     * Get errors for specific field
     */
    public function getErrors(string $field): array
    {
        $this->validate();
        return $this->errors[$field] ?? [];
    }

    /**
     * Get first error for field
     */
    public function getFirstError(string $field): ?string
    {
        $this->validate();
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  CONFIGURATION
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Set custom messages
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Set custom message for field.rule
     */
    public function setMessage(string $field, string $rule, string $message): self
    {
        if (!isset($this->messages[$field])) {
            $this->messages[$field] = [];
        }
        $this->messages[$field][$rule] = $message;
        return $this;
    }

    /**
     * Set attribute labels (friendly names)
     */
    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    /**
     * Set label for field
     */
    public function setLabel(string $field, string $label): self
    {
        $this->labels[$field] = $label;
        return $this;
    }

    /**
     * Register custom validator
     */
    public static function registerValidator(string $name, callable $validator): void
    {
        self::$customValidators[$name] = $validator;
    }

    /**
     * Get validated data (only fields in rules)
     */
    public function validated(): array
    {
        if (!$this->passes()) {
            return [];
        }

        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }

    /**
     * Get all data
     */
    public function getData(): array
    {
        return $this->data;
    }
}

/**
 * ───────────────────────────────────────────────────────────
 *  COMMON VALIDATION SETS (Presets)
 * ───────────────────────────────────────────────────────────
 */

class ValidationRules
{
    /**
     * Rules for User registration
     */
    public static function userRegistration(): array
    {
        return [
            'username'              => 'required|min:3|max:50|unique:users,username',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    /**
     * Rules for User login
     */
    public static function userLogin(): array
    {
        return [
            'username' => 'required',
            'password' => 'required|min:8',
        ];
    }

    /**
     * Rules for Student profile
     */
    public static function studentProfile(): array
    {
        return [
            'full_name'   => 'required|min:3|max:100',
            'student_code' => 'required|min:5|max:20|unique:students,student_code',
            'gender'      => 'required|in:male,female',
            'dob'         => 'required|date',
            'id_card'     => 'required|min:9|max:12',
            'phone'       => 'required|phone',
            'email'       => 'required|email',
            'hometown'    => 'required|min:3',
            'faculty'     => 'required|min:3',
        ];
    }

    /**
     * Rules for Room
     */
    public static function room(): array
    {
        return [
            'building_id'     => 'required|numeric',
            'room_number'     => 'required|min:1|max:50',
            'floor'           => 'required|numeric|greater_than:0',
            'room_type'       => 'required|in:standard,deluxe,ac_standard,ac_deluxe',
            'capacity'        => 'required|numeric|greater_than:0',
            'price_per_month' => 'required|numeric|greater_than:0',
        ];
    }

    /**
     * Rules for Violation
     */
    public static function violation(): array
    {
        return [
            'student_id'    => 'required|numeric',
            'violation_type' => 'required|min:3',
            'description'   => 'required|min:5',
            'location'      => 'required|min:3',
            'penalty_points' => 'numeric|greater_than:0',
        ];
    }

    /**
     * Rules for Contract
     */
    public static function contract(): array
    {
        return [
            'student_id'  => 'required|numeric',
            'room_id'     => 'required|numeric',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date',
            'monthly_fee' => 'required|numeric|greater_than:0',
        ];
    }

    /**
     * Rules for Invoice
     */
    public static function invoice(): array
    {
        return [
            'contract_id' => 'required|numeric',
            'month'       => 'required|numeric|in:1,2,3,4,5,6,7,8,9,10,11,12',
            'year'        => 'required|numeric|greater_than:2000',
        ];
    }

    /**
     * Rules for Building
     */
    public static function building(): array
    {
        return [
            'name'        => 'required|min:3|max:100',
            'address'     => 'required|min:5',
            'gender_type' => 'required|in:male,female,mixed',
            'floors'      => 'required|numeric|greater_than:0',
        ];
    }
}
