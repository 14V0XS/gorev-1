package main

import (
	"net/http"
	"os"

	"github.com/gin-gonic/gin"
)

func main() {
	r := gin.Default()

	r.GET("/api/kategoriler", func(c *gin.Context) {
		veri, err := os.ReadFile("rookieverse/data/categories.json")
		if err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"hata": "JSON bulunamadı"})
			return
		}
		c.Data(http.StatusOK, "application/json", veri)
	})

	r.Run(":8080")
}
