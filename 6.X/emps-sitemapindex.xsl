<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9" exclude-result-prefixes="s">
    <xsl:template match="/">
        <html lang="en">
            <head>
                <meta charset="utf-8"/>
                <title>XML Sitemap Index</title>
                <style type="text/css">
                    body {
                    font-family:"Lucida Grande","Lucida Sans Unicode",Tahoma,Verdana;
                    font-size:13px;
                    }

                    #intro {
                    background-color:#CFEBF7;
                    border:1px #2580B2 solid;
                    padding:5px 13px 5px 13px;
                    margin:10px;
                    }

                    #intro p {
                    line-height:	16.8667px;
                    }

                    td {
                    font-size:11px;
                    }

                    th {
                    text-align:left;
                    padding-right:30px;
                    font-size:11px;
                    }

                    tr.high {
                    background-color:whitesmoke;
                    }

                    #footer {
                    padding:2px;
                    margin:10px;
                    font-size:8pt;
                    color:gray;
                    }

                    #footer a {
                    color:gray;
                    }

                    a {
                    color:black;
                    }
                </style>
            </head>
            <body>
                <h1>XML Sitemap Index</h1>
                <div id="intro">
                    <p>
                        This is a XML Sitemap which is supposed to be processed by search engines like <a href="http://www.google.com">Google</a>, <a href="http://search.msn.com">MSN Search</a> and <a href="http://www.yahoo.com">YAHOO</a>.<br />
                        You can find more information about XML sitemaps on <a href="http://sitemaps.org">sitemaps.org</a> and Google's <a href="http://code.google.com/sm_thirdparty.html">list of sitemap programs</a>.
                    </p>
                </div>
                <div id="content">
                    <table id="sitemap" cellpadding="5">
                        <thead>
                            <tr style="border-bottom:1px black solid;">
                                <th style="text-align:left">URL</th>
                                <th style="text-align:left">Updated at</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:for-each select="s:sitemapindex/s:sitemap">
                                <tr>
                                    <xsl:if test="position() mod 2 != 1">
                                        <xsl:attribute  name="class">high</xsl:attribute>
                                    </xsl:if>
                                    <td class="url">
                                        <xsl:variable name="itemURL">
                                            <xsl:value-of select="s:loc"/>
                                        </xsl:variable>
                                        <a href="{$itemURL}">
                                            <xsl:value-of select="s:loc"/>
                                        </a>
                                    </td>
                                    <td><xsl:value-of select="concat(substring(s:lastmod,0,11),concat(' ', substring(s:lastmod,12,5)))"/></td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>