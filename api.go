package main

import (
	"github.com/glebarez/sqlite" // Saf Go SQLite sürücüsü (CGO gerekmez)
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/cors"
	"gorm.io/gorm"
)

// Modeller
type Course struct {
	ID    uint   `gorm:"primaryKey" json:"id"`
	Title string `json:"title"`
	Link  string `json:"link"`
}

type Game struct {
	ID   uint   `gorm:"primaryKey" json:"id"`
	Name string `json:"name"`
}

type Team struct {
	ID   uint   `gorm:"primaryKey" json:"id"`
	Name string `json:"name"`
	No   int    `json:"no"`
}

type Glossary struct {
	ID          uint   `gorm:"primaryKey" json:"id"`
	Term        string `json:"term"`
	Description string `json:"description"`
}

var DB *gorm.DB

func main() {
	var err error
	// Kalıcı dosya bağlantısı
	DB, err = gorm.Open(sqlite.Open("rookieverse.db"), &gorm.Config{})
	if err != nil {
		panic("Veritabanı dosyası oluşturulamadı!")
	}

	DB.AutoMigrate(&Course{}, &Game{}, &Team{}, &Glossary{})

	app := fiber.New()
	app.Use(cors.New())

	// API Kapıları
	app.Get("/api/courses", func(c *fiber.Ctx) error {
		var items []Course
		DB.Find(&items)
		return c.JSON(items)
	})

	app.Get("/api/games", func(c *fiber.Ctx) error {
		var items []Game
		DB.Find(&items)
		return c.JSON(items)
	})

	app.Get("/api/teams", func(c *fiber.Ctx) error {
		var items []Team
		DB.Find(&items)
		return c.JSON(items)
	})

	app.Get("/api/glossary", func(c *fiber.Ctx) error {
		var items []Glossary
		DB.Find(&items)
		return c.JSON(items)
	})

	// get seed
	app.Get("/api/seed", func(c *fiber.Ctx) error {
		DB.Create(&Course{Title: "FRC Yazılım 101", Link: "https://youtube.com/rookieverse"})
		DB.Create(&Team{Name: "Cezari", No: 6228})
		return c.SendString("Veriler kalıcı olarak kaydedildi! ✅")
	})

	app.Listen(":8080")
}
