import sys
from gtts import gTTS, lang

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
        
        tts = gTTS(text=text, lang=language)
        tts.save(output_file)
        print("Text-to-Speech conversion successful!")
    except Exception as e:
        print(f"Error: {str(e)}")
        sys.exit(1)
