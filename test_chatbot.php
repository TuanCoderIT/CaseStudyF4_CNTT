<?php

/**
 * Test script for chatbot
 * This file will help debug the chatbot's HTML rendering issues
 */

// Khởi tạo phiên làm việc nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối đến CSDL
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background-color: #f8f9fc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .test-panel {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        button {
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            transition: all 0.2s;
        }

        button:hover {
            background-color: #3756d0;
            transform: translateY(-2px);
        }

        pre {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 300px;
            font-family: monospace;
        }

        .result {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #d1d3e2;
            border-radius: 5px;
        }

        .success {
            border-color: #1cc88a;
            background-color: rgba(28, 200, 138, 0.05);
        }

        .error {
            border-color: #e74a3b;
            background-color: rgba(231, 74, 59, 0.05);
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Chatbot Test Page</h1>

        <div class="test-panel">
            <h2>Test Queries</h2>
            <button onclick="testQuery('Phòng trọ gần Đại học Vinh')">Test "Phòng trọ gần Đại học Vinh"</button>
            <button onclick="testQuery('Top 3 phòng trọ được xem nhiều nhất')">Test "Top 3 phòng trọ"</button>
            <button onclick="testQuery('Phòng trọ có giá dưới 2 triệu')">Test "Phòng giá dưới 2tr"</button>
        </div>

        <div class="test-panel">
            <h2>API Response</h2>
            <pre id="apiResponse">-- No data --</pre>
        </div>

        <div class="test-panel">
            <h2>HTML Rendering Test</h2>
            <button onclick="renderHTML()">Render HTML Content</button>
            <div id="htmlRender" class="result"></div>
        </div>

        <?php include_once __DIR__ . '/components/chatbot.php'; ?>
    </div>

    <script>
        let currentResponse = null;

        // Test a specific query
        async function testQuery(query) {
            document.getElementById('apiResponse').textContent = 'Loading...';
            document.getElementById('htmlRender').className = 'result';
            document.getElementById('htmlRender').innerHTML = '';

            try {
                const response = await fetch('/components/chatbot_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: query
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                currentResponse = data;

                document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);

                if (data.html && data.html.trim() !== '') {
                    document.getElementById('htmlRender').innerHTML = '<p>Click "Render HTML Content" to test rendering</p>';
                    document.getElementById('htmlRender').className = 'result success';
                } else {
                    document.getElementById('htmlRender').innerHTML = '<p>No HTML content in response</p>';
                    document.getElementById('htmlRender').className = 'result error';
                }
            } catch (error) {
                console.error('Error testing query:', error);
                document.getElementById('apiResponse').textContent = error.toString();
                document.getElementById('htmlRender').innerHTML = '<p>Error occurred</p>';
                document.getElementById('htmlRender').className = 'result error';
            }
        }

        // Render the HTML content
        function renderHTML() {
            const renderDiv = document.getElementById('htmlRender');

            if (!currentResponse || !currentResponse.html) {
                renderDiv.innerHTML = '<p>No HTML content to render. Run a test query first.</p>';
                renderDiv.className = 'result error';
                return;
            }

            try {
                renderDiv.innerHTML = currentResponse.html;
                renderDiv.className = 'result success';

                // Process any style elements in the HTML
                try {
                    const styleElements = renderDiv.querySelectorAll('style');
                    console.log("Found", styleElements.length, "style elements to process");

                    styleElements.forEach((styleElement, index) => {
                        try {
                            // Clone and append styles to head to prevent them from being removed when moved
                            const newStyle = document.createElement('style');
                            newStyle.textContent = styleElement.textContent;
                            document.head.appendChild(newStyle);
                            console.log(`Style element ${index+1} processed successfully`);
                        } catch (styleError) {
                            console.error(`Error processing style element ${index+1}:`, styleError);
                        }
                    });
                } catch (styleError) {
                    console.error("Error processing style elements:", styleError);
                }
            } catch (error) {
                console.error('Error rendering HTML:', error);
                renderDiv.innerHTML = `
                    <p>Error rendering HTML content:</p>
                    <pre>${error.toString()}</pre>
                    <p>Raw HTML content:</p>
                    <pre>${currentResponse.html.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</pre>
                `;
                renderDiv.className = 'result error';
            }
        }
    </script>
</body>

</html>