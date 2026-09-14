# JT Navi 1.0.0 incelemesi ve 1.0.1 değişiklikleri

Tarih: 14 Eylül 2026. İncelenen başlangıç commit’i: `56ca1cc4d2c455364b503ef0d74f2d499ab41070`.

## Sonuç

JT Navi, Joomla içinde ziyaretçiye kaynak ve sonraki adım sunan hafif bir rehber olarak sağlam bir temele sahip. AI kapalıyken çalışması, kurulum adımlarını ücretsiz sunması ve Joomla’nın kendi uzantı altyapısını kullanması güçlü tarafları. Bununla birlikte ürün, genel amaçlı bir sohbet asistanı veya bütün siteyi anlamsal olarak arayan bir motor değil: her soru bağımsız işleniyor, kurulum akışı kurallarla belirleniyor, arama belirli kaynaklarla sınırlı.

Resmî indirme sayfası ve yol haritasında inceleme tarihinde güncel kararlı sürüm **Joomla 6.1.3**, sonraki kararlı bakım sürümü **6.1.4** olarak görünüyor. 6.2 geliştirme sürümünü uyumluluk hedefi saymadım. [Resmî indirmeler](https://downloads.joomla.org/us/latest), [yol haritası](https://developer.joomla.org/roadmap.html).

Bu çalışma tüm kaynak ağacının statik incelemesini, mevcut canlı demonun belirli akışlarını ve yerel paket/DOM kontrollerini kapsar. Yeni ZIP canlı Joomla’ya kurulmadı; tam entegrasyon, penetrasyon veya erişilebilirlik sertifikasyonu değildir. Release ZIP’inin ikili içeriği indirilip kaynakla karşılaştırılmadı; yayın metadatası, dosya adı ve SHA256 değeri GitHub üzerinden okundu.

## Mimari ve Joomla uyumu

| Alan | Bulgu | Değerlendirme |
| --- | --- | --- |
| Paket | `pkg_jtnavi`, `com_jtnavi` ve `mod_jtnavi` kuruyor; çocuk uzantıların tek başına kaldırılması engelleniyor | Birlikte çalışan bileşen ve modül için uygun |
| MVC | İstek controller’da, yanıt modeli ve kaynak/AI servisleri ayrı, yönetim görünümü ve şablonu ayrı | Joomla MVC yaklaşımıyla uyumlu |
| Servisler | Namespace, `services/provider.php`, MVCFactory ve ModuleDispatcherFactory kullanılıyor | Eski giriş dosyalarına dayanmayan güncel yapı |
| Yönetim | Atum ile uyumlu card/grid/button sınıfları ve Joomla Options/subform alanları | Ayrı bir yönetim framework’ü taşımıyor |
| Önyüz | Cassiopeia içinde çalışan, kendi alanıyla sınırlı CSS ve vanilla JS | Bootstrap yüklenmesine bağımlı olmayan hafif modül |
| Varlıklar | Web Asset Manager ile modül kayıt dosyası açıkça ekleniyor | Doğru; 1.0.1’de her JS/CSS varlığına ayrıca sürüm eklendi |
| Kurulum | InstallerScriptInterface; PHP 8.3+, Joomla 6.x, MySQL ailesi denetimi | Hedef kapsam açık; PostgreSQL desteklenmiyor |
| Güncelleme | Tek paket update server, SHA256, changelog, upgrade yöntemi | Yayın sırası korunmalı; olmayan Release ZIP’i duyurulmamalı |

Joomla 6.1.3 çekirdek kaynaklarında Bootstrap `^5.3.8` bağımlılığı bulunuyor. Dolayısıyla ayrıca Bootstrap paketi/CDN eklemek gerekmiyor. Çekirdekte bulunması her sayfada bütün Bootstrap JavaScript bileşenlerinin otomatik çalışacağı anlamına gelmez; gerektiğinde ilgili varlık etkinleştirilir. JT Navi’nin önyüz kodu Bootstrap JS kullanmıyor. [Çekirdek package.json](https://github.com/joomla/joomla-cms/blob/6.1.3/package.json), [Atum](https://github.com/joomla/joomla-cms/blob/6.1.3/administrator/templates/atum/index.php), [MVC belgeleri](https://manual.joomla.org/docs/building-extensions/components/mvc/), [Web Asset Manager](https://manual.joomla.org/docs/general-concepts/web-asset-manager/).

Joomla 6.x gereksinim tablosu: PHP minimum/desteklenen 8.3.0, önerilen 8.4; MySQL minimum/desteklenen 8.0.13, önerilen 8.4; MariaDB mutlak minimum 10.4, desteklenen 10.6, önerilen 12.0. Önerilen PHP bellek limiti en az 256 MB. Joomla PostgreSQL’i de destekler, ancak JT Navi’nin SQL’i ve ön kontrolü MySQL/MariaDB ile sınırlıdır. Bu ayrım ürün belgelerinde korunmalı. [Resmî teknik gereksinimler](https://manual.joomla.org/docs/get-started/technical-requirements/).

## Güçlü taraflar

- **AI zorunlu değil:** Ortam seçimleri ve kurulum rehberi ücretli API çağrısı yapmıyor. Normal arama da AI kapalıyken çalışıyor.
- **Kontrollü AI kullanımı:** Yönetici etkinleştirmesi, anahtar, ziyaretçi onayı ve kaynak bulunması birlikte aranıyor. En fazla beş kaynak özeti gönderiliyor; sabit HTTPS hedefi kullanılıyor. Hata durumunda yerel kaynaklar korunuyor.
- **Anahtarın istemciye çıkmaması:** Anahtar sunucuda kullanılıyor; ortam değişkeni destekleniyor. Tanı kaydı ham servis yanıtı veya anahtar yerine sınırlı kategori/durum/zaman tutuyor.
- **Erişim süzme:** Makale ve kategori erişimi, yayın durumu, yayın tarihleri, üst kategorilerin erişimi/yayın durumu kontrol ediliyor. Ziyaretçi ve misafir erişim düzeylerinin kesişimi kullanıldığı için yalnızca herkese açık içerik hedefleniyor.
- **Güvenli çıktı yaklaşımı:** Sonuçlar `textContent` ile oluşturuluyor; kaynak HTML’i çalıştırılmıyor. URL protokolü ve kullanıcı adı/şifre içermemesi denetleniyor.
- **İstek koruması:** POST ve CSRF token kontrolü, soru uzunluğu sınırı, oturum başına dakikalık limit ve AI için atomik günlük toplam rezervasyon mevcut. Bunlar resmî CSRF ve güvenli sorgu yaklaşımıyla tutarlı. CSRF’nin erişim kontrolünün yerine geçmediği ayrımı korunmuş. [CSRF](https://manual.joomla.org/docs/security/csrf-protection/), [güvenli sorgular](https://manual.joomla.org/docs/security/secure-db-queries/).
- **Kullanım kolaylığı:** TR/EN metinler, düzenlenebilir başlangıç butonları, sonuçları genişletme/daraltma, görünür odak stilleri ve durum duyuruları mevcut.

## Eksikler ve öncelikler

| Öncelik | Bulgu ve etkisi | Durum / sonraki adım |
| --- | --- | --- |
| Yüksek — kullanıcı akışı | Kaynak kartı aynı sekmede açılıp mevcut sorudan uzaklaştırıyor | **1.0.1’de düzeltildi:** tüm sonuç bağlantıları `_blank`, `noopener noreferrer`; TR/EN ekran okuyucu açıklaması |
| Orta — önbellek | JSON’un üst düzey sürümü var; varlıkların kendi sürümü yok. Joomla 6.1.3 kayıt okuyucusu üst düzey sürümü varlıklara aktarmıyor | **1.0.1’de düzeltildi:** JS ve CSS girişlerinin sürümü açıkça 1.0.1 |
| Orta — niyet algılama | `kur/install` tetikleyicisi Joomla’nın kendisini kurmakla eklenti kurmayı ayırmıyor; “install plugin” de ortam seçimine gidebilir | Sonraki geliştirme: açık Joomla kurulum niyeti, eklenti/şablon kurulum dalı ve regresyon örnekleri |
| Orta — arama kapsamı | Yalnızca makale başlığı/giriş metni taranıyor; tam metin, menüler, etiketler, dosyalar ve diğer bileşenler yok | Kapsam ürün metninde açık; genişleme için Joomla Smart Search entegrasyonu değerlendirilmeli |
| Orta — arama kalitesi | SQL önce en son değişen 100 adayı alıyor, sonra PHP puanlıyor. Büyük sitelerde eski ama daha ilgili sonuç dışarıda kalabilir | İndeksli arama veya veritabanı tarafında uygun sıralama; gerçek içerik hacmiyle ölçüm |
| Orta — Türkçe arama | PHP karakterleri sadeleştiriyor, SQL `LOWER/LIKE` kullanıyor; veritabanı collation’ına bağlı farklılık çıkabilir | Türkçe örneklerle MySQL/MariaDB entegrasyon testi; aynı normalizasyon stratejisi |
| Orta — AI doğrulaması | Başarılı ücretli canlı AI yanıtı yayımlanan belgelerde de doğrulanmamış | Deneysel etiketi korundu. Gerçek anahtarla başarı, limit ve hata senaryoları ayrıca test edilmeli |
| Orta — kötüye kullanım | Oturum limiti yeni oturum açarak aşılabilir; genel AI limiti harcama miktarı değil istek adedidir | Yüksek trafikte sunucu/WAF sınırlaması ve sağlayıcı maliyet kontrolü; anahtarı Options yerine ortamda tutmak tercih edilebilir |
| Düşük — yetki esnekliği | Options butonu `core.admin` ile sınırlı; ayrı `core.options` yetkisi tanımlanmamış | Alt yöneticiye yalnız ayar düzenleme yetkisi vermek istenirse eklenmeli |
| Düşük — erişilebilirlik | Durum/odak yapısı olumlu; mobil, ekran okuyucu, yakınlaştırma ve şablon varyantları tam test edilmedi | Gerçek cihaz/yardımcı teknoloji kontrolü gerekli; yeni sekme bilgisi yardımcı metinle eklendi |
| Düşük — bakım | Tek satırlı yoğun PHP/XML, eski alpha adlandırmaları ve tekrar eden dil anahtarları bakım yükü yaratıyor | Ayrı bir düzenleme sürümünde Joomla kod standartları ve otomatik CI |

Kaynak kartları otomatik web taraması yapmıyor; yönetici tarafından girilen özetler kendiliğinden güncellenmiyor. Bu nedenle resmi bağlantı sunmak, o sayfanın tamamını okuyarak yanıt üretmekle aynı şey değildir. Teknik gereksinimleri sürüm seçicili resmi sayfaya yönlendirmek yanlış sabit sürüm bilgisi üretme riskini azaltıyor, fakat mevcut akış bütün gereksinimleri sohbet içinde ayrıntılı göstermiyor.

Modülü menü atamasıyla gizlemek API endpoint’ini özel yapmaz. Endpoint herkese açık kaynak rehberi olarak tasarlanmış; modülün görünürlüğünden bağımsızdır. Üst kategori dil kısıtları, çok dilli SEF/Itemid yönlendirmesi ve eklentilerin içerik üzerinde uyguladığı özel erişim kuralları ayrıca canlı test gerektirir. Statik denetimde erişim kontrolü mekanizmaları görüldü; bunların bütün Joomla yapılandırmalarında eksiksiz çalıştığı iddia edilmez.

## Canlı demo ve 1.0.1 doğrulaması

14 Eylül 2026’da `https://www.rotabizde.com/` üzerinde Joomla 6.1.3 ibaresi görüldü. “Hosting’e kur” dört adımlı hosting rehberini döndürdü; “Show more sources” teknik gereksinimler bağlantısını gösterdi. “Install Joomla” bilgisayar, hosting ve gereksinim seçeneklerini getirdi. Mevcut cPanel kaynak bağlantısında `target` bulunmadığı DOM üzerinden doğrulandı. Canlı site dosyaları değiştirilmedi.

Yerel DOM testi yeni kodda iç/dış kaynakların, AI/yerel sonuçların, güvensiz URL reddinin, HTML’in metin olarak kalmasının, göster/gizle davranışının, seçenek butonlarının ve iki modülün ayrı durumlarının kontrolünü içerir. Bu testte ağ yanıtları taklittir; ücretli AI veya Joomla veritabanı testi değildir. PHP dosyaları PHP 8.3 dilbilgisi ayrıştırıcısından geçirildi; ortamda PHP çalıştırıcısı bulunmadığı için `php -l` veya gerçek PHP yürütmesi yapılmadı.

## Teslim ve yayın

1.0.1 sadece bağlantı/varlık düzeltmesi ve ilişkili sürüm, test, dokümantasyon değişikliklerini içerir. Veritabanı şeması ve AI davranışı değiştirilmedi. Mevcut kurulumun üzerine paket kurularak güncellenir; son canlı kurulum/upgrade/kaldırma ve JED Checker kontrolü `docs/TESTING.md` kapsamındadır.

Release sahibi kullanıcıdır. Canlı `update.xml` yayımlanmış 1.0.0’a yönelmeye devam eder. `docs/update-1.0.1.xml` yeni ZIP’in hash’iyle hazır bekler; 1.0.1 Release ve tam ZIP yayımlandıktan sonra kökteki `update.xml` yerine alınır. Böylece mevcut Joomla sitelerine indirme adresi henüz olmayan bir güncelleme sunulmaz. Ayrıntı: `docs/RELEASE.md`.
