import sys
from gtts import gTTS, lang
from googletrans import Translator

def translate_text(text, target_language):
    translator = Translator()
    try:
        translated = translator.translate(text, dest=target_language)
        return translated.text
    except Exception as e:
        print(f"Error in translation: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    
    if len(sys.argv) < 4:
        print("Usage: python tts_convert.py <text> <language> <output_file>")
        sys.exit(1)

    text = sys.argv[1]
    language = sys.argv[2]
    output_file = sys.argv[3]

    supported_languages = lang.tts_langs()
    if language not in supported_languages:
        print(f"Error: Language not supported: {language}")
        sys.exit(1)

    try:
        # Step 1: Translate the text to the target language
        translated_text = translate_text(text, language)

        # Step 2: Convert the translated text to speech
        tts = gTTS(text=translated_text, lang=language)
        tts.save(output_file)
        print("Text-to-Speech conversion successful!")
    except Exception as e:
        print(f"Error: {str(e)}")
        sys.exit(1)
