<?php
// archivo: models/Database.php
// Capa de acceso a datos usando la API REST de Supabase (sin PDO ni pdo_pgsql)
// Compatible con cualquier hosting PHP básico (InfinityFree, 000webhost, etc.)

define('SB_URL',  'https://afckeqbqrsccxukgnclr.supabase.co/rest/v1');
define('SB_KEY',  'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImFmY2tlcWJxcnNjY3h1a2duY2xyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzcyOTE0MDUsImV4cCI6MjA5Mjg2NzQwNX0.EgByjo0iaIzBU5371uuJTxUVtcpONG3HW-U_QACTkic');

/**
 * Realiza una petición HTTP a la API REST de Supabase.
 * @param string $method  GET | POST | PATCH | DELETE
 * @param string $tabla   Nombre de la tabla (ej: 'usuarios')
 * @param array  $filtros Filtros de URL (ej: ['correo' => 'eq.algo@mail.com'])
 * @param array  $body    Datos a enviar en el cuerpo (para POST/PATCH)
 * @param array  $select  Columnas a traer (ej: ['id','nombre'])
 * @param bool   $single  Si true, pide un solo objeto
 * @return array|null
 */
function sb_request(string $method, string $tabla, array $filtros = [], array $body = [], array $select = [], bool $single = false): ?array {
    $url = SB_URL . '/' . $tabla;

    // Armar query string
    $params = [];
    foreach ($filtros as $col => $val) {
        $params[] = urlencode($col) . '=' . urlencode($val);
    }
    if (!empty($select)) {
        $params[] = 'select=' . urlencode(implode(',', $select));
    }
    if (!empty($params)) {
        $url .= '?' . implode('&', $params);
    }

    $headers = [
        'apikey: ' . SB_KEY,
        'Authorization: Bearer ' . SB_KEY,
        'Content-Type: application/json',
    ];
    if ($single) {
        $headers[] = 'Accept: application/vnd.pgrst.object+json';
    }
    if ($method === 'POST') {
        $headers[] = 'Prefer: return=representation';
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) return null;

    $data = json_decode($response, true);
    return $data ?? [];
}

/** GET — devuelve array de filas */
function sb_get(string $tabla, array $filtros = [], array $select = []): array {
    return sb_request('GET', $tabla, $filtros, [], $select) ?? [];
}

/** GET — devuelve una sola fila */
function sb_find(string $tabla, array $filtros = [], array $select = []): ?array {
    $result = sb_request('GET', $tabla, $filtros, [], $select, true);
    if (empty($result)) return null;
    // Si vino como array de arrays, tomar el primero
    if (isset($result[0])) return $result[0];
    return $result;
}

/** POST — inserta y devuelve la fila creada */
function sb_insert(string $tabla, array $body): ?array {
    $result = sb_request('POST', $tabla, [], $body);
    if (is_array($result) && isset($result[0])) return $result[0];
    return $result;
}

/** PATCH — actualiza filas que coincidan con $filtros */
function sb_update(string $tabla, array $filtros, array $body): bool {
    $result = sb_request('PATCH', $tabla, $filtros, $body);
    return $result !== null;
}

/** DELETE — elimina filas que coincidan con $filtros */
function sb_delete(string $tabla, array $filtros): bool {
    $result = sb_request('DELETE', $tabla, $filtros);
    return $result !== null;
}

/** Cuenta filas usando la cabecera Prefer: count=exact */
function sb_count(string $tabla, array $filtros = []): int {
    $url = SB_URL . '/' . $tabla;
    $params = [];
    foreach ($filtros as $col => $val) {
        $params[] = urlencode($col) . '=' . urlencode($val);
    }
    // Pedir solo id para minimizar datos
    $params[] = 'select=id';
    if (!empty($params)) $url .= '?' . implode('&', $params);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SB_KEY,
        'Authorization: Bearer ' . SB_KEY,
        'Prefer: count=exact',
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response  = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return is_array($data) ? count($data) : 0;
}
?>
