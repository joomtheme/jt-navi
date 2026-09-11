<?php
/**
 * @package     JT Navi
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace JoomTheme\Component\JtNavi\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** Read-only retrieval. No external crawling and no content-plugin execution. */
final class SourceService
{
    public function __construct(private Registry $params, private DatabaseInterface $db, private string $language) {}

    public static function normalise(string $text): string
    {
        $text = str_replace(['İ', 'I'], ['i', 'i'], $text);
        $text = mb_strtolower($text, 'UTF-8');
        return strtr($text, ['ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c']);
    }

    public static function excerpt(string $html, int $length = 900): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('/\{[^{}]*\}/u', '', $html) ?? '';
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br />'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return mb_substr(trim(preg_replace('/\s+/u', ' ', $text) ?? ''), 0, $length, 'UTF-8');
    }

    public function search(string $question): array
    {
        $tokens = array_values(array_unique(array_filter(preg_split('/[^\p{L}\p{N}]+/u', self::normalise($question)) ?: [],
            static fn ($v) => mb_strlen($v) > 1 && !in_array($v, ['bir', 'icin', 'nasil', 'the', 'and', 'how', 'can', 'want', 'istiyorum', 'istiyor', 'yapmak', 'what', 'to', 'is'], true))));
        $tokens = array_slice($tokens, 0, 8);
        $sources = [];
        if ($this->params->get('use_starters', 1)) {
            $sources = $this->starters();
        }
        foreach (array_slice((array) $this->params->get('sources', []), 0, 100) as $row) {
            $row = (array) $row;
            $lang = (string) ($row['language'] ?? '*');
            $url = trim((string) ($row['url'] ?? ''));
            $parts = parse_url($url);
            if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\x00-\x20]/', $url)
                || !in_array($lang, ['*', $this->language], true)) {
                continue;
            }
            $sources[] = ['title' => self::excerpt((string) ($row['title'] ?? ''), 160), 'url' => $url,
                'summary' => self::excerpt((string) ($row['summary'] ?? ''), 1800),
                'keywords' => self::excerpt((string) ($row['keywords'] ?? ''), 500)];
        }
        if ($tokens && $this->params->get('search_articles', 1)) {
            $sources = array_merge($sources, $this->articles($tokens));
        }
        foreach ($sources as &$source) {
            $title = self::normalise($source['title'] . ' ' . ($source['keywords'] ?? ''));
            $summary = self::normalise($source['summary']);
            $score = 0;
            foreach ($tokens as $token) {
                // Joomla alone should not outrank a more specific topic match.
                $weight = $token === 'joomla' ? 1 : 5;
                $score += str_contains($title, $token) ? $weight * 3 : (str_contains($summary, $token) ? $weight : 0);
            }
            $source['score'] = $score;
            unset($source['keywords']);
        }
        unset($source);
        $sources = array_filter($sources, static fn ($row) => $row['score'] > 0);
        usort($sources, static fn ($a, $b) => $b['score'] <=> $a['score']);
        $unique = [];
        foreach ($sources as $source) {
            if (!isset($unique[$source['url']])) {
                unset($source['score']);
                $unique[$source['url']] = $source;
            }
        }
        return array_slice(array_values($unique), 0, max(1, min(8, (int) $this->params->get('result_limit', 5))));
    }

    private function articles(array $tokens): array
    {
        $db = $this->db;
        // This alpha only exposes articles accessible to both guests and this visitor.
        $levels = array_intersect(Access::getAuthorisedViewLevels(0), Factory::getApplication()->getIdentity()->getAuthorisedViewLevels());
        if (!$levels) { return []; }
        $levelsSql = implode(',', array_map('intval', $levels));
        $now = $db->quote(Factory::getDate()->toSql());
        $nullDate = $db->quote($db->getNullDate());
        $language = $db->quote($this->language);
        $query = $db->getQuery(true)->select($db->quoteName(['a.id', 'a.alias', 'a.catid', 'a.title', 'a.introtext', 'a.language']))
            ->from($db->quoteName('#__content', 'a'))
            ->join('INNER', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->where('a.state = 1')->where('c.published = 1')
            ->where('a.access IN (' . $levelsSql . ')')->where('c.access IN (' . $levelsSql . ')')
            ->where('(a.publish_up IS NULL OR a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $now . ')')
            ->where('(a.publish_down IS NULL OR a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $now . ')')
            ->where("a.language IN ('*', " . $language . ')')->where("c.language IN ('*', " . $language . ')');
        $ancestors = $db->getQuery(true)->select('1')->from($db->quoteName('#__categories', 'p'))
            ->where('p.lft <= c.lft AND p.rgt >= c.rgt AND p.id > 1')
            ->where('(p.published <> 1 OR p.access NOT IN (' . $levelsSql . '))');
        $query->where('NOT EXISTS (' . $ancestors . ')');
        $categories = array_filter(array_map('intval', (array) $this->params->get('categories', [])));
        if ($categories) { $query->where('a.catid IN (' . implode(',', $categories) . ')'); }
        $matches = [];
        // Bound LIKE patterns prevent SQL injection and escape wildcard operators.
        $patterns = [];
        foreach ($tokens as $i => $token) {
            $patterns[$i] = '%' . $db->escape($token, true) . '%';
            $key = ':word' . $i;
            $matches[] = '(LOWER(a.title) LIKE ' . $key . ' OR LOWER(a.introtext) LIKE :intro' . $i . ')';
            $query->bind($key, $patterns[$i]);
            $query->bind(':intro' . $i, $patterns[$i]);
        }
        $query->where('(' . implode(' OR ', $matches) . ')')->order('a.modified DESC, a.id DESC');
        $rows = $db->setQuery($query, 0, 100)->loadObjectList();
        $result = [];
        foreach ($rows as $row) {
            $result[] = ['title' => self::excerpt($row->title, 160),
                'url' => Route::_(RouteHelper::getArticleRoute($row->id . ':' . $row->alias, $row->catid, $row->language), false),
                'summary' => self::excerpt($row->introtext), 'keywords' => ''];
        }
        return $result;
    }

    private function starters(): array
    {
        $tr = str_starts_with($this->language, 'tr');
        return [
            ['title' => $tr ? 'Joomla kurulumuna başla' : 'Start installing Joomla',
             'url' => 'https://downloads.joomla.org/',
             'summary' => $tr ? 'Önce kurulum ortamını seç: kendi bilgisayarın veya hosting. Resmî Joomla indirme sayfasından hedef sürümü seç; kurmadan önce o sürümün teknik gereksinimlerini kontrol et.' : 'Choose a local computer or hosting environment. Select your target release on the official Joomla download page and check its technical requirements before installation.',
             'keywords' => 'install installation kurulum kurmak indir download hosting localhost bilgisayar local'],
            ['title' => $tr ? 'Joomla teknik gereksinimleri' : 'Joomla technical requirements',
             'url' => 'https://manual.joomla.org/docs/get-started/technical-requirements/',
             'summary' => $tr ? 'Teknik gereksinimler sürüme göre değişir. Belgedeki sürüm seçicisinden kuracağın Joomla sürümünü seç; PHP, veritabanı ve web sunucusu gereksinimlerini hosting ortamınla karşılaştır.' : 'Requirements vary by release. Use the documentation version selector for your target Joomla version, then compare PHP, database and web server requirements with your hosting environment.',
             'keywords' => 'gereksinim gereksinimler requirements php mysql database hosting sunucu server'],
            ['title' => $tr ? 'Joomla geliştirici belgeleri' : 'Joomla developer documentation',
             'url' => 'https://manual.joomla.org/docs/',
             'summary' => $tr ? 'Resmî geliştirici belgeleri; bileşen, modül, plugin, MVC, formlar, yetkilendirme ve uzantı geliştirme konularını kapsar. Kod örneklerinde hedef Joomla sürümünü kontrol et.' : 'The official developer manual covers components, modules, plugins, MVC, forms, access control and extension development. Check the target Joomla version when following code examples.',
             'keywords' => 'belge belgeler documentation docs mvc module modul plugin component bilesen gelistirici developer'],
            ['title' => $tr ? 'Joomla yardım ve kullanım belgeleri' : 'Joomla help and user documentation',
             'url' => 'https://docs.joomla.org/',
             'summary' => $tr ? 'Joomla kullanım belgelerine buradan ulaşabilirsin. Kurulum, yönetim ve içerik işlemlerinde okuduğun sayfanın Joomla sürümünü kontrol et.' : 'Find Joomla user documentation here. Check the Joomla version covered by each page before following installation, administration or content-management instructions.',
             'keywords' => 'yardim help kullanici user kurulum installation makale article menu'],
        ];
    }

    /** Built-in, credit-free instructions for an explicitly selected environment. */
    public function installationGuide(string $question): array
    {
        if (!$this->params->get('use_starters', 1)) { return []; }
        $q = self::normalise($question);
        if (!preg_match('/\b(?:kurulum\w*|kurmak|kur|install\w*)\b/u', $q)
            || preg_match('/gereksinim|requirement/', $q)) { return []; }
        $local = (bool) preg_match('/bilgisayar|computer|local/', $q);
        $hosting = (bool) preg_match('/hosting|sunucu/', $q);
        if ($local === $hosting) { return []; }
        $tr = str_starts_with($this->language, 'tr');
        $title = $local ? ($tr ? 'Bilgisayarında Joomla kurulumu' : 'Install Joomla on your computer')
            : ($tr ? 'Hosting üzerinde Joomla kurulumu' : 'Install Joomla on hosting');
        $steps = $local ? ($tr ? [
            'Hedef Joomla sürümünün gereksinimlerine uygun PHP, web sunucusu ve veritabanı içeren bir yerel ortam hazırla.',
            'Sunucu ve veritabanı hizmetlerini başlat. Joomla için boş bir veritabanı ve erişim bilgileri oluştur.',
            'Joomla tam kurulum paketini indir; yerel web dizininde yeni bir klasöre çıkart.',
            'Tarayıcıda bu klasörün localhost adresini aç. Site, yönetici ve veritabanı bilgileriyle kurulum sihirbazını tamamla.'
        ] : [
            'Prepare a local PHP, web server and database environment compatible with your target Joomla release.',
            'Start the web and database services. Create an empty database and its access credentials for Joomla.',
            'Download the Joomla full installation package and extract it into a new folder in your local web directory.',
            'Open that folder through its localhost address. Complete the installer with your site, administrator and database details.'
        ]) : ($tr ? [
            'Hosting panelindeki PHP ve veritabanı sürümlerini hedef Joomla sürümünün gereksinimleriyle karşılaştır.',
            'Hosting panelinde boş bir veritabanı ve yetkili kullanıcı oluştur. Veritabanı sunucusu, ad, kullanıcı ve şifreyi hazırla.',
            'Joomla tam kurulum paketini alan adının boş web dizinine yükleyip çıkart. Mevcut sitenin dosyalarının üzerine yazma.',
            'Alan adını tarayıcıda aç. Kurulum sihirbazına site, yönetici ve hosting sağlayıcının verdiği veritabanı bilgilerini gir.'
        ] : [
            'Check the hosting account’s PHP and database versions against your target Joomla release requirements.',
            'In the hosting panel, create an empty database and an authorised user. Keep the database host, name, username and password ready.',
            'Upload and extract the Joomla full installation package into the domain’s empty web directory. Do not overwrite an existing site.',
            'Open the domain in your browser. Complete the installer using your site, administrator and hosting database details.'
        ]);
        return ['title' => $title, 'steps' => $steps,
            'intro' => $tr ? 'Bu ortam için aşağıdaki adımlarla ilerleyebilirsin.' : 'Follow these steps for your chosen environment.',
            'sources' => [
                ['title' => $local ? ($tr ? 'Yerel ortam hazırlığı' : 'Local environment setup') : ($tr ? 'cPanel hosting hazırlığı' : 'cPanel hosting setup'),
                 'url' => $local ? 'https://guide.joomla.org/user-manual/hosting/local-setup-reference' : 'https://guide.joomla.org/user-manual/hosting/hosting-cpanel-hosting',
                 'summary' => $local ? ($tr ? 'İşletim sistemine göre yerel sunucu seçenekleri.' : 'Local server options for your operating system.') : ($tr ? 'cPanel kullanan hosting hesapları için hazırlık rehberi; başka panellerde adımlar değişebilir.' : 'Preparation for hosting accounts using cPanel; other panels may differ.')],
                ['title' => $tr ? 'Joomla kurulum sihirbazı' : 'Joomla installation walkthrough',
                 'url' => 'https://guide.joomla.org/user-manual/getting-started/getting-started-installing-joomla',
                 'summary' => $tr ? 'Resmî adım adım kurulum rehberi.' : 'Official step-by-step installation guide.'],
                ['title' => $tr ? 'Teknik gereksinimler' : 'Technical requirements',
                 'url' => 'https://manual.joomla.org/docs/get-started/technical-requirements/',
                 'summary' => $tr ? 'Belgedeki sürüm seçicisinden hedef Joomla sürümünü seç.' : 'Select your target Joomla version in the documentation.']
            ]];
    }

    public function journey(string $question): array
    {
        if (!$this->params->get('use_starters', 1)) { return []; }
        $q = self::normalise($question);
        $tr = str_starts_with($this->language, 'tr');
        if (preg_match('/\b(?:kurulum\w*|kurmak|kur|install\w*)\b/u', $q) && !preg_match('/hosting|sunucu|bilgisayar|computer|local|gereksinim|requirement/', $q)) {
            return [
                ['label' => $tr ? 'Bilgisayarımda deneyeceğim' : 'Try on my computer', 'query' => $tr ? 'Bilgisayarımda Joomla kurulum' : 'Local computer Joomla installation'],
                ['label' => $tr ? 'Hosting hesabıma kuracağım' : 'Install on hosting', 'query' => $tr ? 'Hosting Joomla kurulum' : 'Hosting Joomla installation'],
                ['label' => $tr ? 'Gereksinimleri kontrol et' : 'Check requirements', 'query' => $tr ? 'Joomla gereksinimler' : 'Joomla requirements'],
            ];
        }
        return [];
    }
}
