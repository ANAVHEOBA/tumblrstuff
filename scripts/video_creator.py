import sys
import os
from moviepy.editor import AudioFileClip, ImageClip, VideoFileClip, CompositeVideoClip
from PIL import Image
import numpy as np

def create_video_with_audio(audio_path, output_path, background_image_path=None, duration=None):
    """
    Create a video from an audio file and an optional background image.
    If no background image is provided, a solid color background will be used.
    
    Args:
        audio_path (str): Path to the input audio file
        output_path (str): Path for the output video file
        background_image_path (str, optional): Path to background image
        duration (float, optional): Duration of the video. If None, uses audio length
    """
    try:
        
        audio_clip = AudioFileClip(audio_path)
        
        
        if duration is None:
            duration = audio_clip.duration
            
        
        if background_image_path and os.path.exists(background_image_path):
            
            background = ImageClip(background_image_path)
            background = background.resize(width=1920)  
            background = background.set_duration(duration)
        else:
            
            color_clip = ColorClip(size=(1920, 1080), color=(25, 25, 112))
            background = color_clip.set_duration(duration)
        
        
        video = CompositeVideoClip([background])
        final_video = video.set_audio(audio_clip)
        
        
        final_video.write_videofile(
            output_path,
            fps=24,
            codec='libx264',
            audio_codec='aac',
            temp_audiofile='temp-audio.m4a',
            remove_temp=True
        )
        
        # it cleans it up boss
        audio_clip.close()
        final_video.close()
        
        return True, "Video created successfully!"
        
    except Exception as e:
        return False, f"Error creating video: {str(e)}"

class ColorClip(ImageClip):
    """Custom class to create a solid color background"""
    def __init__(self, size, color, duration=None):
        w, h = size
        shape = (h, w, 3)
        array = np.zeros(shape, dtype='uint8')
        array[:, :] = color
        super().__init__(array, duration=duration)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python video_creator.py <audio_file> <output_file> [background_image]")
        sys.exit(1)
        
    audio_file = sys.argv[1]
    output_file = sys.argv[2]
    background_image = sys.argv[3] if len(sys.argv) > 3 else None
    
    success, message = create_video_with_audio(audio_file, output_file, background_image)
    if success:
        print(message)
    else:
        print(f"Error: {message}")
        sys.exit(1)