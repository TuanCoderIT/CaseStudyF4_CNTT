/**
 * Enhanced chatbot data processing and HTML rendering script
 * This script adds improved error handling and debugging for chatbot responses
 */

// Enhanced function to process the HTML from the server
function processChatbotHTML(data, chatMessages) {
  // Enhanced logging to debug the response
  console.log("Processing chatbot response:", data);
  console.log("Response intent:", data.intent || "No intent provided");
  console.log(
    "Has text:",
    Boolean(data.response && data.response.trim() !== "")
  );
  console.log("Has HTML:", Boolean(data.html && data.html.trim() !== ""));
  console.log("HTML content length:", data.htmlLength || 0);

  // First, add the text response if available
  if (data.response && data.response.trim() !== "") {
    addMessage(data.response, "bot", true);
  }

  // Then handle any HTML content (room cards, etc.)
  if (data.html && data.html.trim() !== "") {
    console.log("HTML content found, length:", data.html.length);

    try {
      // Create container for HTML content
      const htmlDiv = document.createElement("div");
      htmlDiv.className = "message bot";

      // Create container for content
      const contentDiv = document.createElement("div");
      contentDiv.className = "message-content";

      // Add HTML to container with error catching
      try {
        console.log("Setting innerHTML with HTML content");
        contentDiv.innerHTML = data.html;
        console.log("innerHTML set successfully");
      } catch (htmlError) {
        console.error("Error setting innerHTML:", htmlError);
        contentDiv.innerHTML = "<p>Lỗi khi hiển thị kết quả HTML.</p>";

        // Try to append a simplified version
        setTimeout(() => {
          try {
            contentDiv.innerHTML =
              "<p>Đang thử hiển thị nội dung theo cách khác...</p>";

            // Create a basic container for room cards if that's what we're expecting
            if (
              data.html.includes("chatbot-room-card") ||
              data.html.includes("room-card")
            ) {
              contentDiv.innerHTML +=
                '<div class="simple-room-container"><p>Vui lòng <a href="/room/search.php" target="_blank">xem kết quả tại đây</a></p></div>';
            }
          } catch (fallbackError) {
            console.error("Even fallback rendering failed:", fallbackError);
          }
        }, 500);
      }

      // Process any style elements in the HTML with enhanced error handling
      try {
        console.log("Looking for style elements in HTML content");
        const styleElements = contentDiv.querySelectorAll("style");
        console.log(
          "Found",
          styleElements ? styleElements.length : 0,
          "style elements to process"
        );

        if (styleElements && styleElements.length > 0) {
          styleElements.forEach((styleElement, index) => {
            try {
              // Clone and append styles to head to prevent them from being removed when moved
              const newStyle = document.createElement("style");
              newStyle.textContent = styleElement.textContent;
              document.head.appendChild(newStyle);
              console.log(`Style element ${index + 1} processed successfully`);
            } catch (individualStyleError) {
              console.error(
                `Error processing style element ${index + 1}:`,
                individualStyleError
              );
            }
          });
        }
      } catch (styleError) {
        console.error("Error processing style elements:", styleError);
      }

      // Append the HTML content
      htmlDiv.appendChild(contentDiv);
      chatMessages.appendChild(htmlDiv);

      // Scroll to show the new message
      if (typeof scrollToBottom === "function") {
        scrollToBottom();
      }

      // Animate the room cards appearance with a slight stagger
      setTimeout(() => {
        try {
          // Target both types of room card classes that might exist
          const cards = contentDiv.querySelectorAll(
            ".chatbot-room-card, .room-card"
          );

          if (cards && cards.length > 0) {
            console.log(`Found ${cards.length} room cards to animate`);

            cards.forEach((card, index) => {
              try {
                card.style.opacity = 0;
                card.style.transform = "translateY(20px)";
                card.style.transition = "all 0.3s ease";

                setTimeout(() => {
                  card.style.opacity = 1;
                  card.style.transform = "translateY(0)";
                }, index * 100);
              } catch (cardAnimError) {
                console.error(`Error animating card ${index}:`, cardAnimError);
              }
            });
          } else {
            console.log("No room cards found to animate");
          }
        } catch (animationError) {
          console.error("Error in card animation:", animationError);
        }
      }, 100);
    } catch (mainError) {
      console.error("Fatal error in HTML rendering:", mainError);
      // Show a user-friendly error
      addMessage(
        "Có lỗi xảy ra khi hiển thị kết quả tìm kiếm. Vui lòng thử lại.",
        "bot",
        false
      );
    }
  } else {
    // No HTML content but logging for debugging
    if (data.debug) {
      console.log("No HTML content to display");
    }
  }
}
