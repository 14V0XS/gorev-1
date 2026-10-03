package main

import (
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/url"
	"os"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/PuerkitoBio/goquery"
	"github.com/glebarez/sqlite"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/logger"
	"github.com/valyala/fasthttp"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
)

// ---------------------------------------------------------------------------
// MODELLER (GORM)
// ---------------------------------------------------------------------------

type Course struct {
	ID          uint   `gorm:"primaryKey" json:"id"`
	Title       string `json:"title"`
	Student     int    `json:"student"`
	Link        string `json:"link"`
	Description string `json:"description"`
	Level       string `json:"level"`
	Category    string `json:"category"`
	Language    string `json:"language"`
	TeamName    string `json:"teamName"`
	TeamNumber  string `json:"teamNumber"`
	CoverImage  string `json:"coverImage"`
}

// Module, bir kursun içindeki ders/modül içerikleridir. Liste bilgisi
// (sıra, başlık) herkese açıkken dosya adresleri yalnızca siteye anonim
// olarak kayıt olunduğunda sunulur (Contents boş kalabilir).
type Module struct {
	ID       uint   `gorm:"primaryKey" json:"id"`
	CourseID uint   `gorm:"index" json:"courseId"` // Course.ID (yerel ilişki)
	ModuleID string `json:"moduleId"`              // sitenin data-moduleid değeri
	Ord      int    `json:"ord"`                   // kurs içindeki sırası
	Title    string `json:"title"`
	Type     string `json:"type"`     // ilk içeriğin tipi: video/youtube/pdf/text
	VideoURL string `json:"videoUrl"` // ilk video adresi
	PDFURL   string `json:"pdfUrl"`   // ilk doküman adresi
	WatchURL string `json:"watchUrl"` // sitedeki görüntüleme sayfası
	// Sitede bir modülde birden fazla içerik olabilir; hepsi burada saklanır.
	Contents []ModuleContent `gorm:"serializer:json" json:"contents"`
}

// ModuleContent, modül sayfasındaki tek bir içerik parçasıdır (sitenin
// module_contents tablosu modeline denktir). Type: video, youtube, pdf, text.
type ModuleContent struct {
	Type  string `json:"type"`
	Title string `json:"title"`
	Data  string `json:"data"` // video/pdf adresi ya da metin içeriği
}

type Contributor struct {
	ID             uint   `gorm:"primaryKey" json:"id"`
	TeamName       string `json:"teamName"`
	TeamNumber     string `json:"teamNumber"`
	ProfilePicPath string `json:"profilePicPath"`
}

type Question struct {
	ID       uint   `gorm:"primaryKey" json:"id"`
	Type     string `json:"type"`
	Content  string `json:"content"`
	Answer   string `json:"answer"`
	Category string `json:"category"`
}

type User struct {
	ID       uint   `gorm:"primaryKey" json:"id"`
	Username string `gorm:"unique;not null" json:"username"`
	Password string `gorm:"not null" json:"-"` // bcrypt hash, JSON'a asla sızdırılmaz
}

// ---------------------------------------------------------------------------
// VERİTABANI (SQLite + GORM)
// ---------------------------------------------------------------------------

var db *gorm.DB

func initDB() *gorm.DB {
	conn, err := gorm.Open(sqlite.Open("rookieverse.db"), &gorm.Config{})
	if err != nil {
		log.Fatalf("Veritabanı bağlantısı kurulamadı: %v", err)
	}
	if err := conn.AutoMigrate(&Course{}, &Module{}, &Contributor{}, &Question{}, &User{}); err != nil {
		log.Fatalf("AutoMigrate başarısız: %v", err)
	}
	return conn
}

// ---------------------------------------------------------------------------
// ANONİM KİMLİK MIDDLEWARE (rv_anon çerezi)
// ---------------------------------------------------------------------------

const anonCookieName = "rv_anon"

func anonIdentity() fiber.Handler {
	return func(c *fiber.Ctx) error {
		// İstemcide çerez yoksa 1 yıllık, HTTPOnly anonim kimlik üret.
		if c.Cookies(anonCookieName) == "" {
			buf := make([]byte, 8)
			if _, err := rand.Read(buf); err == nil {
				c.Cookie(&fiber.Cookie{
					Name:     anonCookieName,
					Value:    "guest_" + hex.EncodeToString(buf),
					Expires:  time.Now().Add(365 * 24 * time.Hour),
					HTTPOnly: true,
				})
			}
		}
		return c.Next()
	}
}

// ---------------------------------------------------------------------------
// HARİCİ SİTE AYARLARI — HTML SCRAPING (goquery)
// ---------------------------------------------------------------------------
// Siteniz (courses.php) veriyi sayfanın HTML'ine gömdüğü için ayrı bir JSON
// adresi yok; bu yüzden sayfayı indirip CSS seçicileriyle kazıyoruz.
// AŞAĞIDAKİ SEÇİCİLERİ SİTENİN YAPISINA GÖRE GÜNCELLEYİN.
//
// rookieverse.net courses.php kart yapısı:
//
//	<div class="course-card" data-comp="FRC" data-lang="tr">
//	  <div class="aspect-video"><img src="uploads/covers/..."></div>
//	  <h3>Kurs Adı</h3>
//	  <span class="text-green-800">Başlangıç</span>  (seviye rozeti)
//	  <span class="text-blue-800">FRC</span>         (kategori rozeti)
//	  <p class="line-clamp-3">Kurs açıklaması</p>
//	  <div class="team-name-button" data-team-number="6430">Kalsedon</div>
//	  <a href="courseDetails.php?course=kurs_...">...</a>
//	  <!-- öğrenci sayısı bu sayfada yok; detay sayfasından çekiliyor -->
//	</div>
const externalSiteURL = "https://www.rookieverse.net/courses.php"

const (
	courseItemSelector  = ".course-card" // her kursu saran eleman
	courseTitleSelector = "h3"           // kurs başlığı
	courseLinkSelector  = "a"            // kurs bağlantısı (href)

	// Listeleme kartındaki diğer alanlar:
	courseDescSelector     = "p.line-clamp-3"       // kurs açıklaması
	courseLevelSelector    = "span.text-green-800"  // seviye rozeti (yeşil)
	courseCategorySelector = "span.text-blue-800"   // kategori rozeti (mavi)
	courseTeamSelector     = ".team-name-button"    // eğitim veren takım
	courseCoverSelector    = "div.aspect-video img" // kapak görseli

	// Öğrenci sayısı listeleme sayfasında gösterilmiyor; her kursun detay
	// sayfasındaki ikonun (data-lucide="users") doğrudan ebeveyn div'inden
	// çekiliyor: <div ...><i data-lucide="users"></i>70 öğrenci</div>
	studentCountIconSelector = `i[data-lucide="users"]`
)

// Modül içerikleri (video/PDF) sitede anonim ziyaretçi akışıyla sunulur:
// addStudent.php, rv_anon çerezine kurs kaydı açar (course_guest_enrollments)
// ve access_course_* çerezi basar. Takım paneli girişi (team-login.php) kurs
// içeriği için GEREKMEZ. Çerezler site_cookies.json'da kalıcı saklanır; böylece
// her sync'te tekrar kayıt atılmaz ve sitenin öğrenci sayacı şişmez.
const (
	scraperUA     = "RookieVerse-API/1.0 (HTML scraping)"
	siteEnrollURL = "https://www.rookieverse.net/addStudent.php"
	cookieJarFile = "site_cookies.json"
)

var (
	courseIDRegex     = regexp.MustCompile(`const\s+course_id\s*=\s*(\d+)`)
	videoFileURLRegex = regexp.MustCompile(`https?://[^\s"'<>]+(?:\.mp4|\.webm|\.m3u8)[^\s"'<>]*`)
	pdfFileURLRegex   = regexp.MustCompile(`[^\s"'<>]+\.pdf`)
)

// ---------------------------------------------------------------------------
// HARİCİ HTTPS SENKRONİZASYON (GET /api/sync)
// ---------------------------------------------------------------------------

func syncHandler(c *fiber.Ctx) error {
	agent := fiber.Get(externalSiteURL)
	agent.Timeout(10 * time.Second)
	agent.Set("User-Agent", scraperUA)
	agent.Set("Accept", "text/html,application/xhtml+xml")

	statusCode, body, errs := agent.String()

	if len(errs) > 0 {
		log.Printf("[sync] siteye ulaşılamadı: %v — mock veriye geçiliyor", errs)
		return fallbackSync(c, fmt.Sprintf("siteye ulaşılamadı: %v", errs[0]))
	}
	if statusCode != fiber.StatusOK {
		log.Printf("[sync] beklenmeyen durum kodu: %d — mock veriye geçiliyor", statusCode)
		return fallbackSync(c, fmt.Sprintf("beklenmeyen durum kodu: %d", statusCode))
	}

	doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
	if err != nil {
		log.Printf("[sync] HTML parse hatası: %v — mock veriye geçiliyor", err)
		return fallbackSync(c, "HTML parse hatası: "+err.Error())
	}

	courses := scrapeCourses(doc)
	if len(courses) == 0 {
		reason := "HTML içinde kurs bulunamadı; seçiciler (" + courseItemSelector + ", " +
			courseTitleSelector + " ...) sitenin yapısıyla eşleşmiyor olabilir"
		log.Printf("[sync] %s — mock veriye geçiliyor", reason)
		return fallbackSync(c, reason)
	}

	// Site oturumu: üyelik bilgileri doluysa giriş yapılır ve modül
	// videolarının adresleri de çekilir; boşsa yalnızca modül listesi gelir.
	sess := newSiteSession()

	// Her kursun detay sayfası: öğrenci sayısı, modül listesi, video adresleri
	modulesByLink := enrichCourses(courses, sess)

	// Kazınan veride kalıcı bir dış ID olmadığı için tabloyu tazele:
	// eskileri sil, yenileri yaz (tekrar sync kopya kayıt üretmez).
	if err := db.Session(&gorm.Session{AllowGlobalUpdate: true}).Delete(&Course{}).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Eski kurslar silinemedi"})
	}
	if err := db.Create(&courses).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Kurslar veritabanına yazılamadı"})
	}

	// Modülleri yerel kurs ID'leriyle eşleştirip yaz
	allModules := make([]Module, 0)
	for i := range courses {
		for _, m := range modulesByLink[courses[i].Link] {
			m.CourseID = courses[i].ID
			allModules = append(allModules, m)
		}
	}
	if err := db.Session(&gorm.Session{AllowGlobalUpdate: true}).Delete(&Module{}).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Eski modüller silinemedi"})
	}
	if len(allModules) > 0 {
		if err := db.Create(&allModules).Error; err != nil {
			return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Modüller veritabanına yazılamadı"})
		}
	}

	seedStaticData()

	return c.JSON(fiber.Map{
		"source":        "scrape",
		"endpoint":      externalSiteURL,
		"coursesSynced": len(courses),
		"modulesSynced": len(allModules),
		"siteLogin":     sess.status(),
		"message":       "BuYuK BABACAN BuYuK BABACAN ZENGİN ADAM",
	})
}

// scrapeCourses, indirilen HTML belgesinden CSS seçicileriyle kursları çıkarır.
func scrapeCourses(doc *goquery.Document) []Course {
	var courses []Course
	doc.Find(courseItemSelector).Each(func(_ int, card *goquery.Selection) {
		title := strings.TrimSpace(card.Find(courseTitleSelector).First().Text())
		if title == "" {
			return // başlığı çıkarılamayan kartı atla
		}

		linkSel := card.Find(courseLinkSelector).First()
		if linkSel.Length() == 0 {
			linkSel = card.Find("a").First() // seçici eşleşmezse ilk bağlantıyı dene
		}
		href, _ := linkSel.Attr("href")

		lang, _ := card.Attr("data-lang")
		teamSel := card.Find(courseTeamSelector).First()
		teamNumber, _ := teamSel.Attr("data-team-number")
		coverSrc, _ := card.Find(courseCoverSelector).First().Attr("src")

		courses = append(courses, Course{
			Title:       title,
			Link:        absoluteURL(strings.TrimSpace(href)),
			Description: strings.TrimSpace(card.Find(courseDescSelector).First().Text()),
			Level:       strings.TrimSpace(card.Find(courseLevelSelector).First().Text()),
			Category:    strings.TrimSpace(card.Find(courseCategorySelector).First().Text()),
			Language:    strings.TrimSpace(lang),
			TeamName:    strings.TrimSpace(teamSel.Text()),
			TeamNumber:  strings.TrimSpace(teamNumber),
			CoverImage:  absoluteURL(strings.TrimSpace(coverSrc)),
			// Student doldurulmaz; enrichStudentCounts detay sayfalarından tamamlar.
		})
	})
	return courses
}

// ---------------------------------------------------------------------------
// SİTE OTURUMU (ANONİM) + MODÜL İÇERİK KAZIMA
// ---------------------------------------------------------------------------

// siteSession, rookieverse.net'te anonim ziyaretçi oturumudur. Fiber'ın HTTP
// istemcisinde çerez deposu olmadığı için Set-Cookie başlıkları elle toplanır
// ve site_cookies.json'da kalıcı saklanır; böylece kursa kayıt bir kez atılır.
type siteSession struct {
	cookies map[string]string // rv_anon, access_course_* vb.
}

func newSiteSession() *siteSession {
	s := &siteSession{cookies: make(map[string]string)}
	if data, err := os.ReadFile(cookieJarFile); err == nil {
		if err := json.Unmarshal(data, &s.cookies); err != nil {
			log.Printf("[sync] çerez dosyası okunamadı: %v", err)
		}
	}
	return s
}

// save, çerezleri diske yazar (sonraki sync'lerde tekrar kayıt atılmasın).
func (s *siteSession) save() {
	if s == nil {
		return
	}
	data, err := json.Marshal(s.cookies)
	if err == nil {
		if err := os.WriteFile(cookieJarFile, data, 0o600); err != nil {
			log.Printf("[sync] çerez dosyası yazılamadı: %v", err)
		}
	}
}

// status, sync yanıtında oturum durumunu bildirir (nil oturumda da çalışır).
func (s *siteSession) status() string {
	if s == nil {
		return "pasif"
	}
	return fmt.Sprintf("anonim oturum (%d çerez)", len(s.cookies))
}

// attach, kayıtlı oturum çerezlerini isteğe ekler.
func (s *siteSession) attach(a *fiber.Agent) {
	if s == nil {
		return
	}
	for k, v := range s.cookies {
		a.Cookie(k, v)
	}
}

// storeCookies, yanıtta gelen Set-Cookie değerlerini depoya işler.
// fasthttp'ın VisitAllCookie'si değeri "ad=değer; öznitelikler" biçiminde
// verdiği için sadeleştirilir ve yalnızca değeri saklanır.
func (s *siteSession) storeCookies(resp *fasthttp.Response) {
	if s == nil {
		return
	}
	resp.Header.VisitAllCookie(func(k, v []byte) {
		raw := string(v)
		if i := strings.IndexByte(raw, ';'); i >= 0 {
			raw = raw[:i] // path, secure vb. öznitelikleri at
		}
		raw = strings.TrimSpace(raw)
		value := raw
		if j := strings.IndexByte(raw, '='); j >= 0 {
			value = strings.TrimSpace(raw[j+1:])
		}
		s.cookies[string(k)] = value
	})
}

func (s *siteSession) get(u string) (int, string, []error) {
	a := fiber.Get(u)
	s.attach(a)
	a.Timeout(10 * time.Second)
	a.Set("User-Agent", scraperUA)
	a.Set("Accept", "text/html,application/xhtml+xml")
	resp := fiber.AcquireResponse()
	a.SetResponse(resp)
	defer fiber.ReleaseResponse(resp)
	code, body, errs := a.String()
	s.storeCookies(resp)
	return code, body, errs
}

func (s *siteSession) post(u, form string) (int, string, []error) {
	a := fiber.Post(u)
	s.attach(a)
	a.Timeout(10 * time.Second)
	a.Set("User-Agent", scraperUA)
	a.ContentType("application/x-www-form-urlencoded")
	a.Body([]byte(form))
	resp := fiber.AcquireResponse()
	a.SetResponse(resp)
	defer fiber.ReleaseResponse(resp)
	code, body, errs := a.String()
	s.storeCookies(resp)
	return code, body, errs
}

// enrollCourse, anonim ziyaretçiyi kursa kaydeder (addStudent.php?id=<iç no>).
// Sitenin student sayacını her çağrıda +1 artırdığı için YALNIZCA modül
// sayfası erişimi reddedildiğinde çağrılmalıdır; çerezler kalıcı olduğundan
// genelde bir kez gerekir.
func (s *siteSession) enrollCourse(internalID string) {
	if s == nil || internalID == "" {
		return
	}
	sc, _, errs := s.post(siteEnrollURL, "id="+url.QueryEscape(internalID))
	if errs != nil || sc != fiber.StatusOK {
		log.Printf("[sync] kursa kayıt (id=%s) başarısız: durum=%d %v", internalID, sc, errs)
		return
	}
	s.save()
	log.Printf("[sync] kursa kayıt tamam (iç id=%s, %d çerez)", internalID, len(s.cookies))
}

// fetchModuleContents, modül sayfasını indirip içerik listesini çıkarır.
// Erişim reddedilirse (403/302 — kursa kayıt yok) bir kez kayıt olup tekrar dener.
func (s *siteSession) fetchModuleContents(watchURL, internalCourseID, moduleTitle string) []ModuleContent {
	if s == nil || watchURL == "" {
		return nil
	}
	sc, body, errs := s.get(watchURL)
	if (sc == fiber.StatusForbidden || sc == fiber.StatusFound) && internalCourseID != "" {
		s.enrollCourse(internalCourseID)
		sc, body, errs = s.get(watchURL)
	}
	if errs != nil || sc != fiber.StatusOK {
		log.Printf("[sync] modül sayfası okunamadı (%s): durum=%d", watchURL, sc)
		return nil
	}
	doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
	if err != nil {
		return nil
	}
	return extractContents(doc, moduleTitle)
}

// stripFragment, adreslerden #çapa kısımlarını soyar (rehber.pdf#toolbar=1 →
// rehber.pdf); API'de temiz dosya adresi kalır.
func stripFragment(u string) string {
	parsed, err := url.Parse(u)
	if err != nil {
		return u
	}
	parsed.Fragment = ""
	return parsed.String()
}

// extractContents, modül sayfasındaki tüm içerikleri çıkarır. Sitede bir
// modülde birden fazla içerik olabilir (sitenin module_contents tablosu):
// video (<video><source>), youtube (<iframe>), doküman (<a download>) ve
// düz metin (<div class="whitespace-pre-line">).
func extractContents(doc *goquery.Document, moduleTitle string) []ModuleContent {
	var out []ModuleContent
	add := func(t, title, u string) {
		u = stripFragment(absoluteURL(strings.TrimSpace(u)))
		if u == "" || u == externalSiteURL {
			return
		}
		out = append(out, ModuleContent{Type: t, Title: strings.TrimSpace(title), Data: u})
	}

	// Videolar
	doc.Find("video").Each(func(_ int, v *goquery.Selection) {
		src, _ := v.Find("source[src]").First().Attr("src")
		if src == "" {
			src, _ = v.Attr("src")
		}
		if strings.TrimSpace(src) != "" {
			add("video", moduleTitle, src)
		}
	})

	// YouTube gömüleri
	doc.Find("iframe[src]").Each(func(_ int, f *goquery.Selection) {
		src, _ := f.Attr("src")
		if strings.Contains(src, "youtube.com/embed") ||
			strings.Contains(src, "youtu.be") ||
			strings.Contains(src, "youtube-nocookie.com") {
			add("youtube", moduleTitle+" (YouTube)", src)
		}
	})

	// Dokümanlar (İndir bağlantıları; başlığı karttan al)
	doc.Find("a[download]").Each(func(_ int, a *goquery.Selection) {
		href, _ := a.Attr("href")
		if strings.TrimSpace(href) == "" {
			return
		}
		title := "Doküman"
		if card := a.Closest("div.bg-white"); card.Length() > 0 {
			if t := strings.TrimSpace(card.Find("div.font-medium").First().Text()); t != "" {
				title = t
			}
		}
		add("pdf", title, href)
	})

	// Düz metin içerikleri
	doc.Find("div.whitespace-pre-line").Each(func(_ int, t *goquery.Selection) {
		txt := strings.TrimSpace(t.Text())
		if txt != "" {
			out = append(out, ModuleContent{Type: "text", Title: moduleTitle, Data: txt})
		}
	})
	return out
}

// enrichCourses, her kursun detay sayfasını tek seferde indirip öğrenci
// sayısını doldurur, modül listesini çıkarır ve her modülün içeriklerini
// (video/youtube/pdf/metin) çeker. Dönen harita kurs linkine göredir.
func enrichCourses(courses []Course, sess *siteSession) map[string][]Module {
	modulesByLink := make(map[string][]Module)
	for i := range courses {
		if courses[i].Link == "" {
			continue
		}
		a := fiber.Get(courses[i].Link)
		a.Timeout(10 * time.Second)
		a.Set("User-Agent", scraperUA)
		a.Set("Accept", "text/html,application/xhtml+xml")

		statusCode, body, errs := a.String()
		if len(errs) > 0 || statusCode != fiber.StatusOK {
			log.Printf("[sync] detay sayfası okunamadı (%s): durum=%d", courses[i].Link, statusCode)
			continue
		}
		doc, err := goquery.NewDocumentFromReader(strings.NewReader(body))
		if err != nil {
			continue
		}

		courses[i].Student = parseIntFromText(
			doc.Find(studentCountIconSelector).First().Parent().Text())

		modules := scrapeModuleRefs(doc, courses[i].Link)
		if len(modules) > 0 {
			// addStudent.php'nin beklediği kurs iç numarası (örn. const course_id = 85)
			var internalID string
			if m := courseIDRegex.FindStringSubmatch(body); m != nil {
				internalID = m[1]
			}
			for j := range modules {
				contents := sess.fetchModuleContents(modules[j].WatchURL, internalID, modules[j].Title)
				modules[j].Contents = contents
				for _, ct := range contents {
					switch ct.Type {
					case "video":
						if modules[j].VideoURL == "" {
							modules[j].VideoURL = ct.Data
						}
						if modules[j].Type == "" {
							modules[j].Type = "video"
						}
					case "pdf":
						if modules[j].PDFURL == "" {
							modules[j].PDFURL = ct.Data
						}
						if modules[j].Type == "" || modules[j].Type == "text" {
							modules[j].Type = "pdf"
						}
					case "youtube":
						if modules[j].Type == "" || modules[j].Type == "text" {
							modules[j].Type = "youtube"
						}
					case "text":
						if modules[j].Type == "" {
							modules[j].Type = "text"
						}
					}
				}
			}
		}
		modulesByLink[courses[i].Link] = modules
	}
	return modulesByLink
}

// scrapeModuleRefs, detay sayfasındaki div.modulePlayer elemanlarından modül
// listesini çıkarır; giriş gerektirmeden çalışır.
func scrapeModuleRefs(doc *goquery.Document, courseLink string) []Module {
	var modules []Module
	doc.Find("div.modulePlayer").Each(func(_ int, s *goquery.Selection) {
		moduleID, _ := s.Attr("data-moduleid")
		if moduleID == "" {
			return
		}
		ordStr, _ := s.Attr("data-ord")
		ord, _ := strconv.Atoi(ordStr)
		modules = append(modules, Module{
			ModuleID: moduleID,
			Ord:      ord,
			Title:    strings.TrimSpace(s.Find("h4").First().Text()),
			WatchURL: moduleWatchURL(courseLink, moduleID, ord-1),
		})
	})
	return modules
}

// moduleWatchURL, sitenin JS'inin kullandığı adres kalıbını birebir üretir:
// moduleDetails.php?course=<kod>&id=<modül>&ord=<sıra-1>
func moduleWatchURL(courseLink, moduleID string, ordZero int) string {
	u, err := url.Parse(courseLink)
	if err != nil {
		return ""
	}
	q := u.Query()
	q.Set("id", moduleID)
	q.Set("ord", strconv.Itoa(ordZero))
	u.Path = "/moduleDetails.php"
	u.RawQuery = q.Encode()
	return u.String()
}

// parseIntFromText, "1.240 öğrenci" gibi bir metinden sayıyı çıkarır
// (basamak dışındaki tüm karakterleri atar).
func parseIntFromText(s string) int {
	var digits strings.Builder
	for _, r := range s {
		if r >= '0' && r <= '9' {
			digits.WriteRune(r)
		}
	}
	n, _ := strconv.Atoi(digits.String())
	return n
}

// absoluteURL, göreli href'leri (/course/1 gibi) site adresine göre tam URL'ye çevirir.
func absoluteURL(href string) string {
	if href == "" {
		return ""
	}
	base, err := url.Parse(externalSiteURL)
	if err != nil {
		return href
	}
	ref, err := url.Parse(href)
	if err != nil {
		return href
	}
	return base.ResolveReference(ref).String()
}

// fallbackSync: bağlantı hatası / timeout / seçici eşleşmemezliği durumunda
// sistem çökmeden varsayılan mock verileri veritabanına yazar ve durumu bildirir.
func fallbackSync(c *fiber.Ctx, reason string) error {
	mock := mockCourses()
	if err := db.Session(&gorm.Session{AllowGlobalUpdate: true}).Delete(&Course{}).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Eski kurslar silinemedi"})
	}
	if err := db.Create(&mock).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Mock veriler veritabanına yazılamadı"})
	}
	seedStaticData()

	return c.JSON(fiber.Map{
		"source":        "mock",
		"endpoint":      externalSiteURL,
		"reason":        reason,
		"coursesSynced": len(mock),
		"message":       "Harici siteye ulaşılamadı; varsayılan mock veriler kaydedildi",
	})
}

// seedStaticData: harici kaynakta bulunmayan contributor/question tablolarını
// yalnızca boşken mock veriyle doldurur.
func seedStaticData() {
	var count int64
	db.Model(&Contributor{}).Count(&count)
	if count == 0 {
		contributors := mockContributors()
		db.Create(&contributors)
	}
	db.Model(&Question{}).Count(&count)
	if count == 0 {
		questions := mockQuestions()
		db.Create(&questions)
	}
}

// ---------------------------------------------------------------------------
// REST ENDPOINT HANDLER'LARI
// ---------------------------------------------------------------------------

func coursesHandler(c *fiber.Ctx) error {
	var courses []Course
	if err := db.Order("id asc").Find(&courses).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Kurslar listelenemedi"})
	}
	return c.JSON(fiber.Map{"count": len(courses), "courses": courses})
}

func courseByIDHandler(c *fiber.Ctx) error {
	id, err := strconv.ParseUint(c.Params("id"), 10, 64)
	if err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Geçersiz kurs ID"})
	}
	var course Course
	result := db.First(&course, id)
	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{
			"error": fmt.Sprintf("ID %d olan kurs bulunamadı", id),
		})
	}
	if result.Error != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Kurs sorgulanamadı"})
	}
	// Mobil uygulamanın (gorev1_yigitapp) beklediği takma alanlarla birlikte:
	type courseView struct {
		Course
		Team  string `json:"team"`  // = teamName
		Desc  string `json:"desc"`  // = description
		Cover string `json:"cover"` // = coverImage
	}
	return c.JSON(courseView{Course: course, Team: course.TeamName, Desc: course.Description, Cover: course.CoverImage})
}

func contributorsHandler(c *fiber.Ctx) error {
	var contributors []Contributor
	if err := db.Order("id asc").Find(&contributors).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Katkıda bulunanlar listelenemedi"})
	}
	return c.JSON(fiber.Map{"count": len(contributors), "contributors": contributors})
}

func questionsByTypeHandler(c *fiber.Ctx) error {
	qType := c.Params("type")
	var questions []Question
	if err := db.Where("type = ?", qType).Order("id asc").Find(&questions).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Sorular listelenemedi"})
	}
	return c.JSON(fiber.Map{"type": qType, "count": len(questions), "questions": questions})
}

// GET /api/modules — tüm modüller (kurs adlarıyla birlikte).
// ?courseId=X verilirse mobil uygulamanın beklediği biçimde düz dizi döner.
func modulesHandler(c *fiber.Ctx) error {
	if cid := c.Query("courseId"); cid != "" {
		id, err := strconv.ParseUint(cid, 10, 64)
		if err != nil {
			return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Geçersiz courseId"})
		}
		var modules []Module
		if err := db.Where("course_id = ?", id).Order("ord asc").Find(&modules).Error; err != nil {
			return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Modüller listelenemedi"})
		}
		type moduleMobile struct {
			Module
			Order int `json:"order"` // mobil sözleşme: order = ord
		}
		views := make([]moduleMobile, 0, len(modules))
		for _, m := range modules {
			views = append(views, moduleMobile{Module: m, Order: m.Ord})
		}
		return c.JSON(views)
	}

	var modules []Module
	if err := db.Order("course_id asc, ord asc").Find(&modules).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Modüller listelenemedi"})
	}
	var courses []Course
	db.Find(&courses)
	titles := make(map[uint]string, len(courses))
	for _, crs := range courses {
		titles[crs.ID] = crs.Title
	}
	type moduleView struct {
		Module
		CourseTitle string `json:"courseTitle"`
	}
	views := make([]moduleView, 0, len(modules))
	for _, m := range modules {
		views = append(views, moduleView{Module: m, CourseTitle: titles[m.CourseID]})
	}
	return c.JSON(fiber.Map{"count": len(views), "modules": views})
}

// GET /api/lessons?moduleId=X — mobil uygulama sözleşmesi: modülün içerikleri
// ders listesine açılır (her içerik bir "ders"; content = video/pdf adresi
// ya da metin). Dosya adresi bilinmiyorsa boş dizi döner (uygulama
// "Henüz ders eklenmemiş" görür; bozuk oynatıcı açılmaz).
func lessonsHandler(c *fiber.Ctx) error {
	mid, err := strconv.ParseUint(c.Query("moduleId"), 10, 64)
	if err != nil || mid == 0 {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "moduleId gerekli"})
	}
	var m Module
	result := db.First(&m, mid)
	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{"error": "Modül bulunamadı"})
	}
	type lessonMobile struct {
		ID       uint   `json:"id"`
		ModuleID uint   `json:"moduleId"`
		CourseID uint   `json:"courseId"`
		Title    string `json:"title"`
		Type     string `json:"type"`
		Content  string `json:"content"`
		Duration string `json:"duration"`
		Order    int    `json:"order"`
	}
	lessons := make([]lessonMobile, 0, len(m.Contents))
	for i, ct := range m.Contents {
		if ct.Data == "" {
			continue
		}
		lessons = append(lessons, lessonMobile{
			ID:       m.ID*1000 + uint(i) + 1,
			ModuleID: m.ID,
			CourseID: m.CourseID,
			Title:    ct.Title,
			Type:     ct.Type,
			Content:  ct.Data,
			Order:    i + 1,
		})
	}
	return c.JSON(lessons)
}

// GET /api/courses/:id/modules — tek kursun modülleri (sırayla)
func courseModulesHandler(c *fiber.Ctx) error {
	id, err := strconv.ParseUint(c.Params("id"), 10, 64)
	if err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Geçersiz kurs ID"})
	}
	var course Course
	result := db.First(&course, id)
	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return c.Status(fiber.StatusNotFound).JSON(fiber.Map{"error": "Kurs bulunamadı"})
	}
	var modules []Module
	db.Where("course_id = ?", id).Order("ord asc").Find(&modules)
	return c.JSON(fiber.Map{"course": course, "count": len(modules), "modules": modules})
}

// ---------------------------------------------------------------------------
// KİMLİK DOĞRULAMA (register / login)
// ---------------------------------------------------------------------------

type authRequest struct {
	Username string `json:"username"`
	Password string `json:"password"`
}

func registerHandler(c *fiber.Ctx) error {
	var req authRequest
	if err := c.BodyParser(&req); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Geçersiz istek gövdesi"})
	}
	if req.Username == "" || req.Password == "" {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Kullanıcı adı ve şifre zorunludur"})
	}

	var count int64
	db.Model(&User{}).Where("username = ?", req.Username).Count(&count)
	if count > 0 {
		return c.Status(fiber.StatusConflict).JSON(fiber.Map{"error": "Bu kullanıcı adı zaten alınmış"})
	}

	hashed, err := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
	if err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Şifre işlenemedi"})
	}

	user := User{Username: req.Username, Password: string(hashed)}
	if err := db.Create(&user).Error; err != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Kullanıcı kaydedilemedi"})
	}

	return c.Status(fiber.StatusCreated).JSON(fiber.Map{
		"message":  "Kayıt başarılı",
		"username": user.Username,
	})
}

func loginHandler(c *fiber.Ctx) error {
	var req authRequest
	if err := c.BodyParser(&req); err != nil {
		return c.Status(fiber.StatusBadRequest).JSON(fiber.Map{"error": "Geçersiz istek gövdesi"})
	}

	var user User
	result := db.Where("username = ?", req.Username).First(&user)
	if errors.Is(result.Error, gorm.ErrRecordNotFound) {
		return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{"error": "Kullanıcı adı veya şifre hatalı"})
	}
	if result.Error != nil {
		return c.Status(fiber.StatusInternalServerError).JSON(fiber.Map{"error": "Giriş doğrulanamadı"})
	}
	if bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(req.Password)) != nil {
		return c.Status(fiber.StatusUnauthorized).JSON(fiber.Map{"error": "Kullanıcı adı veya şifre hatalı"})
	}

	return c.JSON(fiber.Map{"message": "Giriş başarılı", "username": user.Username})
}

// ---------------------------------------------------------------------------
// MOCK VERİLER (graceful fallback)
// ---------------------------------------------------------------------------

func mockCourses() []Course {
	return []Course{
		{ID: 1, Title: "Go Temelleri: Söz Dizimi ve Tipler", Student: 128, Link: "https://rookieverse.dev/courses/go-temelleri"},
		{ID: 2, Title: "Fiber v2 ile Hızlı REST API", Student: 96, Link: "https://rookieverse.dev/courses/fiber-rest-api"},
		{ID: 3, Title: "GORM ile Veritabanı Yönetimi", Student: 74, Link: "https://rookieverse.dev/courses/gorm-essentials"},
		{ID: 4, Title: "SQLite ile Gömülü Veritabanı", Student: 51, Link: "https://rookieverse.dev/courses/sqlite-gomulu-veritabani"},
		{ID: 5, Title: "Harici API'lerden Veri Çekme", Student: 88, Link: "https://rookieverse.dev/courses/veri-cekme"},
	}
}

func mockContributors() []Contributor {
	return []Contributor{
		{ID: 1, TeamName: "Rocket Raptors", TeamNumber: "TM-01", ProfilePicPath: "/assets/contributors/rocket-raptors.png"},
		{ID: 2, TeamName: "Code Catalysts", TeamNumber: "TM-02", ProfilePicPath: "/assets/contributors/code-catalysts.png"},
		{ID: 3, TeamName: "Bug Hunters", TeamNumber: "TM-03", ProfilePicPath: "/assets/contributors/bug-hunters.png"},
		{ID: 4, TeamName: "Loop Legends", TeamNumber: "TM-04", ProfilePicPath: "/assets/contributors/loop-legends.png"},
		{ID: 5, TeamName: "Null Pointers", TeamNumber: "TM-05", ProfilePicPath: "/assets/contributors/null-pointers.png"},
	}
}

func mockQuestions() []Question {
	return []Question{
		{ID: 1, Type: "multiple_choice", Category: "Go", Content: "Go'da bir fonksiyon kaç değer döndürebilir?", Answer: "Birden fazla; Go çoklu dönüş değerlerini destekler."},
		{ID: 2, Type: "true_false", Category: "Fiber", Content: "Fiber, Fasthttp üzerine inşa edilmiş bir web çatısıdır.", Answer: "Doğru"},
		{ID: 3, Type: "multiple_choice", Category: "GORM", Content: "GORM'da şema otomatik oluşturmak için hangi metot kullanılır?", Answer: "AutoMigrate"},
		{ID: 4, Type: "open_ended", Category: "SQLite", Content: "SQLite'ın sunucusuz (serverless) olmasının avantajını açıklayın.", Answer: "Ayrı sunucu süreci gerektirmez; veritabanı tek bir dosyada yaşar."},
		{ID: 5, Type: "true_false", Category: "HTTP", Content: "HTTPOnly çerezler JavaScript üzerinden okunamaz.", Answer: "Doğru"},
		{ID: 6, Type: "open_ended", Category: "Go", Content: "Goroutine nedir, OS thread'inden farkı nedir?", Answer: "Go çalışma zamanının yönettiği hafif eşzamanlılık birimidir; OS thread'ine göre çok daha ucuzdur."},
	}
}

// ---------------------------------------------------------------------------
// MAIN — uygulama ve rota yapılandırması
// ---------------------------------------------------------------------------

func main() {
	db = initDB()

	app := fiber.New(fiber.Config{AppName: "RookieVerse API v1.0"})

	app.Use(logger.New())
	app.Use(anonIdentity())

	api := app.Group("/api")
	api.Get("/sync", syncHandler)
	api.Get("/courses", coursesHandler)
	api.Get("/courses/:id", courseByIDHandler)
	api.Get("/courses/:id/modules", courseModulesHandler)
	api.Get("/modules", modulesHandler)
	api.Get("/lessons", lessonsHandler)
	api.Get("/contributors", contributorsHandler)
	api.Get("/questions/:type", questionsByTypeHandler)
	api.Post("/register", registerHandler)
	api.Post("/login", loginHandler)

	// Web arayüzü: web/index.html varsa kökten servis edilir (http://localhost:3000/)
	if _, err := os.Stat("./web/index.html"); err == nil {
		app.Static("/", "./web")
	}

	log.Println("RookieVerse API ayakta: http://localhost:3000 (web arayüzü: /)")
	log.Fatal(app.Listen(":3000"))
}
