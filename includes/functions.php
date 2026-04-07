<?php
/**
 * Các hàm xử lý sản phẩm - trích xuất từ link (không dùng API)
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Chuẩn hóa URL
 */
function normalizeUrl($url) {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    return $url;
}

/**
 * Nhận diện nền tảng từ URL
 */
function detectPlatform($url) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) {
        return 'other';
    }
    $host = strtolower($host);
    if (strpos($host, 'tiktok.com') !== false) {
        return 'tiktok';
    }
    if (strpos($host, 'shopee.') !== false) {
        return 'shopee';
    }
    return 'other';
}

/**
 * Chuyển slug URL PDP TikTok thành tiêu đề đọc được (vd: ao-thun-boxy → Áo thun boxy)
 */
function slugToReadableTitle($slug) {
    if (!is_string($slug) || $slug === '') {
        return '';
    }
    $slug = rawurldecode(str_replace('+', ' ', $slug));
    $parts = preg_split('/[\-_]+/u', $slug, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return '';
    }
    $out = [];
    foreach ($parts as $w) {
        if (function_exists('mb_convert_case')) {
            $out[] = mb_convert_case($w, MB_CASE_TITLE, 'UTF-8');
        } else {
            $out[] = ucfirst(strtolower($w));
        }
    }
    return implode(' ', $out);
}

/**
 * Lấy tiêu đề từ đường dẫn PDP TikTok Shop (.../pdp/slug/123)
 */
function extractTiktokPdpSlugTitle($url) {
    if (preg_match('~/pdp/([^/]+)/(\d+)(?:[?#]|$)~i', $url, $m)) {
        return slugToReadableTitle($m[1]);
    }
    return '';
}

/**
 * Tìm ảnh CDN TikTok Shop trong HTML (p16-oec-*.ibyteimg.com, ...)
 */
function extractTiktokCdnImageFromHtml($html) {
    if (!is_string($html) || $html === '') {
        return '';
    }
    if (preg_match('#https://p\d+-oec-[^"\'\s<>]+?\.(?:jpeg|jpg|png|webp)(?:\?[^"\'\s<>]*)?#i', $html, $m)) {
        return html_entity_decode($m[0]);
    }
    return '';
}

/**
 * Link mở sản phẩm TikTok (giống nút "Mua trên TikTok")
 */
function tiktokViewProductUrl($numericId) {
    $id = preg_replace('/\D/', '', (string) $numericId);
    return $id !== '' ? 'https://www.tiktok.com/view/product/' . $id : '';
}

/**
 * Trích xuất ID sản phẩm từ URL
 */
function extractExternalId($url) {
    // TikTok Shop PDP: .../shop/vn/pdp/slug/1732138660655040271 (nhiều segment trước pdp)
    if (preg_match('~/pdp/[^/]+/(\d+)(?:[?#]|$)~i', $url, $m)) {
        return 'tt_' . $m[1];
    }
    // TikTok: /view/product/123
    if (preg_match('~/tiktok\.com/view/product/(\d+)(?:[?#]|$)~i', $url, $m)) {
        return 'tt_' . $m[1];
    }
    // Shopee: ...-i.SHOPID.ITEMID
    if (preg_match('~-i\.(\d+)\.(\d+)(?:[?#]|$)~', $url, $m)) {
        return 'sp_' . $m[1] . '_' . $m[2];
    }
    // Shopee: /username/ITEMID (link rút gọn hoặc affiliate redirect)
    if (preg_match('~/([a-zA-Z0-9_-]+)/(\d{10,20})(?:[?#]|$)~', $url, $m)) {
        if (stripos($url, 'shopee.vn') !== false || stripos($url, 's.shopee') !== false) {
            return 'sp_u_' . $m[2];
        }
    }
    return '';
}

/**
 * Tải trang web với cURL
 */
function fetchPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
    ]);

    $body = curl_exec($ch);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'body' => $body ?: '',
        'effective_url' => $effectiveUrl ?: $url,
        'http_code' => $httpCode
    ];
}

/**
 * Giải mã gzip nếu cần
 */
function decodeGzip($body) {
    if (!is_string($body) || strlen($body) < 10) {
        return $body;
    }
    if ($body[0] === "\x1f" && $body[1] === "\x8b" && function_exists('gzdecode')) {
        $decoded = @gzdecode($body);
        if ($decoded !== false && $decoded !== '') {
            return $decoded;
        }
    }
    return $body;
}

/**
 * Trích xuất thông tin từ HTML (OG tags, JSON-LD)
 */
function extractFromHtml($html) {
    $data = [];

    // OG Image
    if (preg_match('/<meta\s[^>]*property\s*=\s*["\']og:image["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
        $data['image'] = html_entity_decode($m[1]);
    } elseif (preg_match('/<meta\s[^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*property\s*=\s*["\']og:image["\']/i', $html, $m)) {
        $data['image'] = html_entity_decode($m[1]);
    }

    // OG Title
    if (preg_match('/<meta\s[^>]*property\s*=\s*["\']og:title["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
        $data['name'] = html_entity_decode($m[1]);
    } elseif (preg_match('/<meta\s[^>]*content\s*=\s*["\']([^"\']+)["\'][^>]*property\s*=\s*["\']og:title["\']/i', $html, $m)) {
        $data['name'] = html_entity_decode($m[1]);
    }

    // OG Description
    if (preg_match('/<meta\s[^>]*property\s*=\s*["\']og:description["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
        $data['description'] = html_entity_decode($m[1]);
    }

    // Twitter image
    if (empty($data['image']) && preg_match('/<meta\s[^>]*name\s*=\s*["\']twitter:image["\'][^>]*content\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
        $data['image'] = html_entity_decode($m[1]);
    }

    // JSON-LD Product
    if (preg_match_all('#<script[^>]*type\s*=\s*["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $blocks)) {
        foreach ($blocks[1] as $raw) {
            $raw = trim($raw);
            $json = json_decode($raw, true);
            if (!is_array($json)) continue;

            $nodes = isset($json['@graph']) ? $json['@graph'] : [$json];
            foreach ($nodes as $node) {
                if (!is_array($node)) continue;
                $type = $node['@type'] ?? '';
                $isProduct = $type === 'Product' || (is_array($type) && in_array('Product', $type));

                if ($isProduct) {
                    if (!empty($node['name']) && empty($data['name'])) {
                        $data['name'] = $node['name'];
                    }
                    if (!empty($node['description']) && empty($data['description'])) {
                        $data['description'] = is_string($node['description']) ? $node['description'] : '';
                    }
                    if (!empty($node['image'])) {
                        $img = $node['image'];
                        if (is_string($img) && empty($data['image'])) {
                            $data['image'] = $img;
                        } elseif (is_array($img) && !empty($img[0]) && empty($data['image'])) {
                            $data['image'] = $img[0];
                        }
                    }
                    // Offers/Price
                    if (!empty($node['offers']['price']) && empty($data['price'])) {
                        $data['price'] = (float) $node['offers']['price'];
                    }
                }
            }
        }
    }

    // TikTok __NEXT_DATA__
    if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $matches)) {
        $json = json_decode($matches[1], true);
        if ($json) {
            $pageProps = $json['props']['pageProps'] ?? [];
            if (!empty($pageProps['productData'])) {
                $product = $pageProps['productData'];
                if (!empty($product['title']) && empty($data['name'])) {
                    $data['name'] = $product['title'];
                }
                if (!empty($product['description']) && empty($data['description'])) {
                    $data['description'] = $product['description'];
                }
                if (!empty($product['price']['min']) && empty($data['price'])) {
                    $data['price'] = $product['price']['min'] / 100;
                }
                if (!empty($product['images'][0]) && empty($data['image'])) {
                    $data['image'] = $product['images'][0];
                }
            }
        }
    }

    // Shopee price from meta
    if (preg_match('/itemprop\s*=\s*["\']price["\'][^>]*content\s*=\s*["\']([\d.]+)["\']/i', $html, $m)) {
        $data['price'] = (float) $m[1];
    }

    if (empty($data['image'])) {
        $cdn = extractTiktokCdnImageFromHtml($html);
        if ($cdn !== '') {
            $data['image'] = $cdn;
        }
    }

    
    // ===== SHOPEE: Trích từ SIGI_STATE (dữ liệu JS nhúng trong trang) =====
    if (preg_match('/"name"\s*:\s*"((?:[^"\\\\]|\\\\.){10,200})"/u', $html, $m)) {
        $name = stripcslashes(html_entity_decode($m[1]));
        if (empty($data['name']) && strlen($name) > 5 && strpos($name, 'shopee') === false) {
            $data['name'] = $name;
        }
    }
    if (preg_match('/"price"\s*:\s*([0-9]+)/', $html, $m)) {
        if (empty($data['price']) && (int)$m[1] > 100) {
            $data['price'] = (int)$m[1];
        }
    }
    if (empty($data['image']) && preg_match('/https:\\/\\/[a-z0-9.-]+\\.shopee\\.vn[^"\'\\s<>]{10,200}\\.(?:jpg|jpeg|png|webp)/i', $html, $m)) {
        $data['image'] = $m[0];
    }
    return $data;
}

/**
 * Trích title/image từ tham số og_info trên URL chia sẻ TikTok
 *
 * @return array{name?:string,image?:string}
 */
function extractOgInfoFromTikTokUrl($url) {
    $out = [];
    $parts = parse_url($url);
    if (empty($parts['query'])) {
        return $out;
    }
    parse_str($parts['query'], $q);
    if (empty($q['og_info'])) {
        return $out;
    }
    $raw = $q['og_info'];
    $json = json_decode(rawurldecode($raw), true);
    if (!is_array($json)) {
        $json = json_decode(urldecode($raw), true);
    }
    if (!is_array($json)) {
        return $out;
    }
    if (!empty($json['title'])) {
        $out['name'] = $json['title'];
    }
    if (!empty($json['image'])) {
        $out['image'] = $json['image'];
    }
    return $out;
}

/**
 * Hàm chính: Trích xuất thông tin sản phẩm từ URL
 * @return array Thông tin sản phẩm
 */
function fetchProductInfo($url) {
    $result = [
        'success' => false,
        'url' => '',
        'name' => '',
        'price' => 0,
        'original_price' => 0,
        'discount' => 0,
        'image' => '',
        'description' => '',
        'images' => [],
        'platform' => 'other',
        'external_id' => '',
        'effective_url' => '',
        'affiliate_link' => '',
    ];

    $url = normalizeUrl($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        return $result;
    }

    $result['url'] = $url;
    $result['platform'] = detectPlatform($url);

    // Link chia sẻ TikTok có og_info=... trong query
    $og = extractOgInfoFromTikTokUrl($url);
    if (!empty($og['name'])) {
        $result['name'] = $og['name'];
    }
    if (!empty($og['image'])) {
        $result['image'] = $og['image'];
        $result['images'] = [$og['image']];
    }

    $fetched = fetchPage($url);
    $body = decodeGzip($fetched['body']);
    $effectiveUrl = $fetched['effective_url'];

    $result['effective_url'] = $effectiveUrl;
    if ($effectiveUrl !== $url) {
        $result['platform'] = detectPlatform($effectiveUrl);
    }

    $extId = extractExternalId($effectiveUrl);
    if ($extId === '') {
        $extId = extractExternalId($url);
    }
    $result['external_id'] = $extId;

    if ($body !== '' && strlen($body) > 200) {
        $extracted = extractFromHtml($body);

        if (!empty($extracted['name'])) {
            $result['name'] = $extracted['name'];
        }
        if (!empty($extracted['description'])) {
            $result['description'] = $extracted['description'];
        }
        if (!empty($extracted['price'])) {
            $result['price'] = $extracted['price'];
        }
        if (!empty($extracted['image'])) {
            $result['image'] = $extracted['image'];
            $result['images'] = [$extracted['image']];
        }
    }

    // Tiêu đề từ slug PDP: .../pdp/ao-thun-boxy/1732138660655040271
    $slugTitle = extractTiktokPdpSlugTitle($effectiveUrl);
    if ($slugTitle === '') {
        $slugTitle = extractTiktokPdpSlugTitle($url);
    }
    if ($result['name'] === '' && $slugTitle !== '') {
        $result['name'] = $slugTitle;
    }

    if ($result['image'] === '' && $body !== '' && strlen($body) > 200) {
        $cdn = extractTiktokCdnImageFromHtml($body);
        if ($cdn !== '') {
            $result['image'] = $cdn;
            $result['images'] = [$cdn];
        }
    }

    // Nút mua: TikTok → /view/product/{id}; Shopee → URL sau redirect
    if (preg_match('/^tt_(\d+)$/', $result['external_id'], $tm)) {
        $result['affiliate_link'] = tiktokViewProductUrl($tm[1]);
    } elseif ($result['platform'] === 'shopee' && $effectiveUrl !== '') {
        $result['affiliate_link'] = $effectiveUrl;
    } else {
        $result['affiliate_link'] = $effectiveUrl !== '' ? $effectiveUrl : $url;
    }

    if ($result['name'] === '') {
        $platformLabel = match($result['platform']) {
            'tiktok' => 'TikTok Shop',
            'shopee' => 'Shopee',
            default => 'Sản phẩm'
        };
        $result['name'] = $platformLabel . ' - ' . substr(md5($url), 0, 8);
    }

    $result['success'] = true;
    return $result;
}

/**
 * Tạo slug từ tên sản phẩm
 */
function createSlug($string) {
    $search = ['à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ',
        'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ',
        'ì', 'í', 'ị', 'ỉ', 'ĩ',
        'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ',
        'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ',
        'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ',
        'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ',
        'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ',
        'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ',
        'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ',
        'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ',
        'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ', 'Đ'];

    $replace = ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
        'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
        'i', 'i', 'i', 'i', 'i',
        'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
        'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
        'y', 'y', 'y', 'y', 'y', 'd',
        'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A',
        'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E',
        'I', 'I', 'I', 'I', 'I',
        'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O',
        'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U',
        'Y', 'Y', 'Y', 'Y', 'Y', 'D'];

    $string = str_replace($search, $replace, $string);
    $string = preg_replace('/[^a-zA-Z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', trim($string));
    $string = trim($string, '-');

    return strtolower($string);
}

/**
 * Lưu sản phẩm vào database
 */
function saveProduct($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO products (
        platform, external_id, source_url, name, slug, description,
        price, original_price, discount, image, images, affiliate_link,
        category_id, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");

    $slug = createSlug($data['name']);
    $images = json_encode($data['images'] ?? []);
    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    $affiliate = trim($data['affiliate_link'] ?? '');
    if ($affiliate === '') {
        $affiliate = trim($data['url'] ?? '');
    }

    // 13 placeholder: 6s (platform…description) + 2d (price, original_price) + i (discount) + 3s (image, images, affiliate) + i (category_id)
    $stmt->bind_param(
        'ssssssddisssi',
        $data['platform'],
        $data['external_id'],
        $data['url'],
        $data['name'],
        $slug,
        $data['description'],
        $data['price'],
        $data['original_price'],
        $data['discount'],
        $data['image'],
        $images,
        $affiliate,
        $categoryId
    );

    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Cập nhật sản phẩm
 */
function updateProduct($conn, $id, $data) {
    $stmt = $conn->prepare("UPDATE products SET
        name = ?, description = ?, price = ?, original_price = ?, discount = ?,
        image = ?, images = ?, category_id = ?, status = ?, updated_at = NOW()
    WHERE id = ?");

    $images = json_encode($data['images'] ?? []);
    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
    $status = $data['status'] ?? 'active';

    $stmt->bind_param(
        'ssddissisi',
        $data['name'],
        $data['description'],
        $data['price'],
        $data['original_price'],
        $data['discount'],
        $data['image'],
        $images,
        $categoryId,
        $status,
        $id
    );

    return $stmt->execute();
}

/**
 * Xóa sản phẩm
 */
function deleteProduct($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Kiểm tra sản phẩm đã tồn tại theo external_id
 */
function productExists($conn, $externalId) {
    if ($externalId === '') return false;
    $stmt = $conn->prepare('SELECT id FROM products WHERE external_id = ?');
    $stmt->bind_param('s', $externalId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Kiểm tra URL đã tồn tại
 */
function urlExists($conn, $url) {
    $stmt = $conn->prepare('SELECT id FROM products WHERE source_url = ? OR affiliate_link = ? LIMIT 1');
    $stmt->bind_param('ss', $url, $url);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Lấy danh sách sản phẩm
 */
function getProducts($conn, $limit = 50, $offset = 0, $categoryId = null, $search = '', $status = 'active') {
    $where = "WHERE 1=1";
    $params = [];
    $types = '';

    if ($categoryId) {
        $where .= " AND category_id = ?";
        $params[] = $categoryId;
        $types .= 'i';
    }

    if ($search !== '') {
        $where .= " AND (name LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }

    if ($status !== 'all') {
        $where .= " AND status = ?";
        $params[] = $status;
        $types .= 's';
    }

    $sql = "SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            $where
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Lấy 1 sản phẩm theo ID
 */
function getProduct($conn, $id) {
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name
                            FROM products p
                            LEFT JOIN categories c ON p.category_id = c.id
                            WHERE p.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Lấy tất cả danh mục
 */
function getCategories($conn) {
    $result = $conn->query("SELECT * FROM categories ORDER BY sort_order, name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Format giá tiền
 */
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

/**
 * Query string phân trang trang chủ (giữ category, search; page chỉ thêm khi > 1)
 *
 * @param array<string, scalar> $extra category_id, search, ...
 */
function shopPagerHref(array $extra, int $pageNum): string {
    $q = $extra;
    if ($pageNum > 1) {
        $q['page'] = $pageNum;
    }
    if ($q === []) {
        return 'index.php';
    }
    return '?' . http_build_query($q);
}

/**
 * In HTML phân trang (prev / số trang / next)
 *
 * @param array<string, scalar> $pagerExtra
 */
function renderShopPagination(int $page, int $totalPages, array $pagerExtra, string $extraClass = ''): void {
    if ($totalPages <= 1) {
        return;
    }
    $class = trim('pagination ' . $extraClass);
    echo '<nav class="' . htmlspecialchars($class) . '" aria-label="Phân trang sản phẩm">';

    if ($page > 1) {
        $href = htmlspecialchars(shopPagerHref($pagerExtra, $page - 1), ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $href . '" class="pagination__arrow" rel="prev" title="Trang trước"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>';
    } else {
        echo '<span class="pagination__arrow pagination__arrow--disabled" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>';
    }

    $winStart = max(1, $page - 2);
    $winEnd = min($totalPages, $page + 2);
    if ($winStart > 1) {
        $href = htmlspecialchars(shopPagerHref($pagerExtra, 1), ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $href . '" class="pagination__num">1</a>';
        if ($winStart > 2) {
            echo '<span class="pagination__ellipsis">&hellip;</span>';
        }
    }
    for ($i = $winStart; $i <= $winEnd; $i++) {
        if ($i === $page) {
            echo '<span class="pagination__num pagination__num--current" aria-current="page">' . (int) $i . '</span>';
        } else {
            $href = htmlspecialchars(shopPagerHref($pagerExtra, $i), ENT_QUOTES, 'UTF-8');
            echo '<a href="' . $href . '" class="pagination__num">' . (int) $i . '</a>';
        }
    }
    if ($winEnd < $totalPages) {
        if ($winEnd < $totalPages - 1) {
            echo '<span class="pagination__ellipsis">&hellip;</span>';
        }
        $href = htmlspecialchars(shopPagerHref($pagerExtra, $totalPages), ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $href . '" class="pagination__num">' . (int) $totalPages . '</a>';
    }

    if ($page < $totalPages) {
        $href = htmlspecialchars(shopPagerHref($pagerExtra, $page + 1), ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $href . '" class="pagination__arrow" rel="next" title="Trang sau"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>';
    } else {
        echo '<span class="pagination__arrow pagination__arrow--disabled" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>';
    }

    echo '</nav>';
}
