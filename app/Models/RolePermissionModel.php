<?php

namespace App\Models;

class RolePermissionModel extends BaseModel
{
    protected string $table = 'role_permissions';

    /** Ordered nav modules — used in sidebar and permissions matrix */
    public const MODULES = [
        'dashboard'    => ['label' => 'Dashboard',       'icon' => 'speedometer2',            'url' => 'dashboard'],
        'members'      => ['label' => 'Zawodnicy',       'icon' => 'people',                  'url' => 'members'],
        'licenses'     => ['label' => 'Licencje',        'icon' => 'card-checklist',           'url' => 'licenses'],
        'finances'     => ['label' => 'Finanse',         'icon' => 'cash-stack',              'url' => 'finances'],
        'competitions' => ['label' => 'Zawody',          'icon' => 'trophy',                  'url' => 'competitions'],
        'judges'       => ['label' => 'Sędziowie',       'icon' => 'person-badge',            'url' => 'judges'],
        'club_fees'    => ['label' => 'Opłaty PZSS',    'icon' => 'bank',                    'url' => 'club-fees'],
        'reports'      => ['label' => 'Raporty',         'icon' => 'file-earmark-bar-graph',  'url' => 'reports'],
        'config'       => ['label' => 'Konfiguracja',    'icon' => 'gear',                    'url' => 'config'],
    ];

    public const ROLES = [
        'admin'      => ['label' => 'Administrator', 'color' => 'danger'],
        'zarzad'     => ['label' => 'Zarząd',        'color' => 'warning'],
        'instruktor' => ['label' => 'Instruktor',    'color' => 'info'],
        'sędzia'     => ['label' => 'Sędzia',        'color' => 'primary'],
    ];

    /** Fallback when role_permissions table doesn't exist yet */
    public const DEFAULTS = [
        'admin'      => ['dashboard','members','licenses','finances','competitions','judges','club_fees','reports','config'],
        'zarzad'     => ['dashboard','members','licenses','finances','competitions','judges','club_fees','reports','config'],
        'instruktor' => ['dashboard','members','licenses','competitions','reports'],
        'sędzia'     => ['dashboard','competitions'],
    ];

    /** Per-request cache: [role => [module => bool]] */
    private static ?array $cache = null;

    /**
     * Returns the full permissions matrix loaded from DB.
     * Returns [role => [module => bool]].
     */
    public function getMatrix(): array
    {
        $matrix = [];
        foreach (self::ROLES as $role => $_) {
            foreach (self::MODULES as $mod => $_) {
                $matrix[$role][$mod] = false;
            }
        }

        try {
            $rows = $this->db->query("SELECT role, module FROM role_permissions")->fetchAll();
            foreach ($rows as $r) {
                if (isset($matrix[$r['role']][$r['module']])) {
                    $matrix[$r['role']][$r['module']] = true;
                }
            }
        } catch (\PDOException) {
            // Table not yet created — use defaults
            foreach (self::DEFAULTS as $role => $modules) {
                foreach ($modules as $mod) {
                    if (isset($matrix[$role][$mod])) {
                        $matrix[$role][$mod] = true;
                    }
                }
            }
        }

        return $matrix;
    }

    /**
     * Replaces all permissions with the given matrix.
     * $matrix: [role => [module, ...]] (only granted ones)
     */
    public function saveMatrix(array $matrix): void
    {
        // admin always keeps full access — enforce it
        $matrix['admin'] = array_keys(self::MODULES);

        $this->db->exec("DELETE FROM role_permissions");
        $stmt = $this->db->prepare("INSERT INTO role_permissions (role, module) VALUES (?, ?)");
        foreach ($matrix as $role => $modules) {
            if (!isset(self::ROLES[$role])) continue;
            foreach ((array)$modules as $mod) {
                if (isset(self::MODULES[$mod])) {
                    $stmt->execute([$role, $mod]);
                }
            }
        }
        self::$cache = null;
    }

    /**
     * Returns array of module keys the given role can access.
     * Uses per-request static cache; falls back to DEFAULTS if table missing.
     */
    public static function modulesForRole(string $role): array
    {
        if (self::$cache === null) {
            try {
                $pdo  = \App\Helpers\Database::pdo();
                $rows = $pdo->query("SELECT role, module FROM role_permissions")->fetchAll();
                self::$cache = [];
                foreach ($rows as $r) {
                    self::$cache[$r['role']][] = $r['module'];
                }
            } catch (\PDOException) {
                self::$cache = self::DEFAULTS;
            }
        }
        return self::$cache[$role] ?? (self::DEFAULTS[$role] ?? []);
    }

    /** Quick check: can this role access this module? */
    public static function can(string $role, string $module): bool
    {
        return in_array($module, self::modulesForRole($role), true);
    }
}
