<?php
class ShortUrl
{
    private $conn;
    private $table       = "short_urls";
    private $visits_table = "url_visits";

    public $id;
    public $original_url;
    public $short_code;
    public $creator_ip;
    public $max_uses;
    public $expires_at;
    public $visit_count;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create(): bool
    {
        $query = "INSERT INTO " . $this->table . "
                  SET original_url = :original_url,
                      short_code   = :short_code,
                      creator_ip   = :creator_ip,
                      max_uses     = :max_uses,
                      expires_at   = :expires_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':original_url', $this->original_url);
        $stmt->bindParam(':short_code',   $this->short_code);
        $stmt->bindParam(':creator_ip',   $this->creator_ip);
        $stmt->bindParam(':max_uses',     $this->max_uses);
        $stmt->bindParam(':expires_at',   $this->expires_at);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function findByCode(string $code): bool
    {
        $query = "SELECT id, original_url, short_code, creator_ip,
                         max_uses, expires_at, visit_count, created_at
                  FROM " . $this->table . "
                  WHERE short_code = :code
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id           = $row['id'];
            $this->original_url = $row['original_url'];
            $this->short_code   = $row['short_code'];
            $this->creator_ip   = $row['creator_ip'];
            $this->max_uses     = $row['max_uses'];
            $this->expires_at   = $row['expires_at'];
            $this->visit_count  = $row['visit_count'];
            $this->created_at   = $row['created_at'];
            return true;
        }
        return false;
    }

    public function isActive(): bool
    {
        if ($this->expires_at && strtotime($this->expires_at) < time()) {
            return false;
        }
        if ($this->max_uses !== null && $this->visit_count >= $this->max_uses) {
            return false;
        }
        return true;
    }

    public function registerVisit(string $ip, string $userAgent): bool
    {
        $q1 = "UPDATE " . $this->table . "
               SET visit_count = visit_count + 1
               WHERE short_code = :code";
        $s1 = $this->conn->prepare($q1);
        $s1->bindParam(':code', $this->short_code);
        $s1->execute();

        $q2 = "INSERT INTO " . $this->visits_table . "
               SET short_code  = :code,
                   visitor_ip  = :ip,
                   user_agent  = :ua";
        $s2 = $this->conn->prepare($q2);
        $s2->bindParam(':code', $this->short_code);
        $s2->bindParam(':ip',   $ip);
        $s2->bindParam(':ua',   $userAgent);

        return $s2->execute();
    }

    public function getStats(string $code): ?array
    {
        if (!$this->findByCode($code)) return null;

        $q1 = "SELECT DATE(visited_at) as day, COUNT(*) as visits
               FROM " . $this->visits_table . "
               WHERE short_code = :code
               AND visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
               GROUP BY DATE(visited_at)
               ORDER BY day DESC";
        $s1 = $this->conn->prepare($q1);
        $s1->bindParam(':code', $code);
        $s1->execute();
        $visits_by_day = $s1->fetchAll(PDO::FETCH_ASSOC);

        $q2 = "SELECT visited_at, visitor_ip, user_agent
               FROM " . $this->visits_table . "
               WHERE short_code = :code
               ORDER BY visited_at DESC
               LIMIT 5";
        $s2 = $this->conn->prepare($q2);
        $s2->bindParam(':code', $code);
        $s2->execute();
        $recent_visits = $s2->fetchAll(PDO::FETCH_ASSOC);

        return [
            'short_code'    => $this->short_code,
            'original_url'  => $this->original_url,
            'total_visits'  => (int)$this->visit_count,
            'max_uses'      => $this->max_uses,
            'expires_at'    => $this->expires_at,
            'created_at'    => $this->created_at,
            'is_active'     => $this->isActive(),
            'visits_by_day' => $visits_by_day,
            'recent_visits' => $recent_visits,
        ];
    }

    public function codeExists(string $code): bool
    {
        $query = "SELECT id FROM " . $this->table . " WHERE short_code = :code LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
