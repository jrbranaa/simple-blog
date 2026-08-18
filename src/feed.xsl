<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/rss/channel">
<html>
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><xsl:value-of select="title"/></title>
<style>
    body {
        background: #fafaf8;
        color: #5A5A5A;
        font-family: Arial, sans-serif;
        line-height: 1.7;
        margin: 0;
        padding: 24px 15px 60px;
    }
    .feed-container {
        max-width: 680px;
        margin: 0 auto;
    }
    .feed-notice {
        background: #fff;
        border: 1px solid #e0e0d8;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .feed-notice svg { flex-shrink: 0; color: #D75B70; }
    .feed-notice p { margin: 0; font-size: 0.95rem; }
    .feed-notice code {
        background: #f0f0ea;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.9em;
        word-break: break-all;
    }
    h1 { color: #333; margin-bottom: 4px; }
    .feed-description { color: #666; margin-top: 0; }
    .feed-item {
        border-bottom: 1px solid #e0e0d8;
        padding: 20px 0;
    }
    .feed-item:last-child { border-bottom: none; }
    .feed-item h2 { margin: 0 0 4px; font-size: 1.15rem; }
    .feed-item h2 a { color: #333; text-decoration: none; }
    .feed-item h2 a:hover { text-decoration: underline; }
    .feed-item time { color: #666; font-size: 0.85rem; }
    .feed-item p { margin: 8px 0 0; }
</style>
</head>
<body>
<div class="feed-container">
    <div class="feed-notice">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
        <p>This is an RSS feed. Copy its URL into your feed reader to subscribe:<br/>
        <code><xsl:value-of select="link"/>feed.xml</code></p>
    </div>

    <h1><xsl:value-of select="title"/></h1>
    <p class="feed-description"><xsl:value-of select="description"/></p>

    <xsl:for-each select="item">
    <div class="feed-item">
        <h2><a href="{link}"><xsl:value-of select="title"/></a></h2>
        <time><xsl:value-of select="pubDate"/></time>
        <p><xsl:value-of select="description"/></p>
    </div>
    </xsl:for-each>
</div>
</body>
</html>
</xsl:template>

</xsl:stylesheet>
