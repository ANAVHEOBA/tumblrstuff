from gtts import lang

# Print supported languages
supported_languages = lang.tts_langs()
print("Supported Languages:")
for language_code, language_name in supported_languages.items():
    print(f"{language_code}: {language_name}")
