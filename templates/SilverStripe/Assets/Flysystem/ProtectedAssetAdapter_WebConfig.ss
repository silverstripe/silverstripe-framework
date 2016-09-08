<?xml version="1.0" encoding="UTF-8"?>
<!--
Configuration to block web access to secure folders
-->
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <clear />
                <rule name="BlockProtectedAssets" patternSyntax="Wildcard" stopProcessing="true">
                    <match url="*" />
                    <action type="CustomResponse" statusCode="403" statusReason="Forbidden: Access is denied." statusDescription="You do not have permission to view this directory or page using the credentials that you supplied." />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
