# Copilot Instructions

All files in the multiflexi/*.app.json directory must conform to the schema available at: https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json

## MultiFlexi Schema Compliance

The environment variable types must use only the following allowed values:
- "string" - for text values
- "file-path" - for file paths (not "file")
- "email" - for email addresses
- "url" - for URLs
- "integer" - for integer numbers (not "number")
- "float" - for floating point numbers
- "bool" - for boolean values
- "password" - for sensitive password fields
- "set" - for predefined sets of values
- "text" - for longer text content

Recent fixes applied:
- Changed IMAP_PORT from "number" to "integer"
- Changed RESULT_FILE from "file" to "file-path" 
- Changed ISDOC_FILE from "file" to "file-path"
