#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os, math
from PIL import Image, ImageDraw, ImageFont
from tkinter import Tk, messagebox
from tkinter.filedialog import askopenfilename, asksaveasfilename

def get_font(fonts_folder):
    if not os.path.exists(fonts_folder) or not os.path.isdir(fonts_folder):
        return    
    folder_fonts = [f for f in os.listdir(fonts_folder) if f.endswith('.ttf') or f.endswith('.otf')]
    font_files =  [os.path.join(fonts_folder, font_file) for font_file in folder_fonts]
    print("\n可用字体：")
    for index, font in enumerate(font_files):
        print(f"{index + 1}. {font}")
    while True:
        font_choice = int(get_float_value("\n请选择字体编号：", 1, None))
        if 1 <= font_choice <= len(font_files):
            return font_files[font_choice - 1]
        else:
            print("\n请输入有效的编号")

def calc_font_size(image, percentage):
    width, height = image.size
    max_dimension = min(width, height)
    return int(max_dimension * percentage / 100)

def calc_density(image, percentage):
    width, height = image.size
    max_dimension = min(width, height)
    return int(max_dimension * percentage / 100)

def get_image_path():
    while True:
        Tk().withdraw()
        image_path = askopenfilename()
        if not image_path:
            response = messagebox.askyesno("确认", "未选择图片，是否真的要放弃制作？")
            if response:
                print("\n用户选择放弃保存")
                return ""
            else:
                continue
        else:
            return image_path

def save_file(img, new_filename, extension):
    while True:
        save_path = asksaveasfilename(defaultextension=extension, initialfile=new_filename)
        if save_path:
            img.save(save_path)
            print("\n图片已保存")
            return
        else:
            response = messagebox.askyesno("确认", "未选择保存路径，是否放弃保存？")
            if response:
                print("\n用户选择放弃保存")
                return
            else:
                continue

def get_float_value(prompt, default=None, range=range(0, 100)):
    while True:
        try:
            value = input(prompt).strip()
            if not value and default is not None:
                return float(default)
            
            elif range is not None: 
                if float(value) < range.start:
                    raise ValueError
                
                elif float(value) > range.stop:
                    raise ValueError
            
            return float(value)
        except ValueError:
            if range is not None: 
                print(f"\n请输入一个有效的数字")
            else:
                print(f"\n请输入一个有效范围[{range.start}~{range.stop}]的数字")

def calculate_distance(theight, density, angle, twidth=None):

    # 检查输入值的有效性
    d = theight + density
    if d <= 0:
        return "错误：垂线距离必须为正数"
    
    # 将角度标准化到0-360度范围内
    angle = angle % 360
    
    # 特殊角度情况处理
    if angle == 0 or angle == 180:
        return twidth + density
    
    if angle == 90 or angle == 270:
        return theight + density
    
    try:
        # 将角度转换为弧度并计算正切值
        tan_value = math.tan(math.radians(angle))
        
        # 计算距离
        d = 2 * d / tan_value
        
        # 根据角度象限确定结果的符号
        # if 90 < angle < 270:
        #     d = -d
        
        return abs(d)  # 返回绝对值，因为我们只关心实际距离
        
    except ZeroDivisionError:
        return "计算错误：角度导致除以零"
    except Exception as e:
        return f"计算错误：{str(e)}"

def add_tiled_watermark():

    fonts_folder = "./fonts"
    if not os.path.exists(fonts_folder) or not os.path.isdir(fonts_folder):
        fonts_folder = path_env = os.environ.get("windir")
        fonts_folder = f"{path_env}\\Fonts"
        if not os.path.exists(fonts_folder) or not os.path.isdir(fonts_folder):
            print("\n无法找到字体目录")
    else:
        print("\n已使用本地字体目录")

    image_path = get_image_path()
    if not image_path:
        return
    try:
        img = Image.open(image_path)
        original_mode = img.mode
        img.convert("RGBA")

    except Exception:
        print("\n无法打开文件")
        return

    watermark_text = input("\n请输入水印文字：")
    alpha_percentage = get_float_value("\n请输入水印透明度（默认30）：", 30)
    watermark_alpha = round(255 - 255 * alpha_percentage / 100)

    font_size_percentage = get_float_value("\n请输入水印文字大小（图片短边百分百，默认5）：", 5)
    font_size = calc_font_size(img, font_size_percentage)
 
    density_percentage = get_float_value("\n请输入平铺密度（图片短边百分比，默认2）：", 2)
    density = calc_density(img, density_percentage)

    angle = get_float_value("\n请输入旋转角度（默认45）：", 45, range(-360, 360))

    selected_font = get_font(fonts_folder)
    if selected_font == None:
        print(f"\n无效的字体")
        return

    txt_layer = Image.new("RGBA", img.size, (255, 255, 255, 0))
    draw = ImageDraw.Draw(txt_layer)
    font = ImageFont.truetype(selected_font, font_size)
    bbox = draw.textbbox((0, 0), watermark_text, font)
    twidth = bbox[2] - bbox[0]
    theight = bbox[3] - bbox[1]

    if (twidth == 0 or theight == 0 or density == 0 or img.height  == 0 or img.width == 0):
        print(f"density的值为: {density}，是否为0：{density == 0}，类型为: {type(density)}")
        print(f"theight的值为: {theight}，是否为0：{theight == 0}，类型为: {type(theight)}")
        print(f"twidth的值为: {twidth}，是否为0：{twidth == 0}，类型为: {type(twidth)}")
        print(f"img.height的值为: {img.height}，是否为0：{img.height == 0}，类型为: {type(img.height)}")
        print(f"img.width的值为: {img.width}，是否为0：{img.width == 0}，类型为: {type(img.width)}")
        print("以上参数均不能为0，请检查相关设置")
    else:

        # 创建一个小的水印图像
        watermark = Image.new("RGBA", (twidth + density, theight + density), (255, 255, 255, 0))
        watermark_draw = ImageDraw.Draw(watermark)
        fill_with_alpha = (0, 0, 0, watermark_alpha)
        xypos = density // 2
        watermark_draw.text((xypos, xypos), watermark_text, font=font, fill=fill_with_alpha)
        rotated_watermark = watermark.rotate(angle, expand=True)
        rotated_width, rotated_height = rotated_watermark.size
        
        density_x = calculate_distance(theight, density, angle, twidth)
        
        print(density, angle, rotated_width, density_x)
 
        xcount = math.ceil(img.width / density_x) 
        #xcount = math.ceil(img.width / rotated_width)
        ycount = math.ceil(img.height / rotated_height)
        xstart = (img.width - xcount * rotated_width) // 2
        ystart = (img.height - ycount * rotated_height) // 2

        for ypos in range(ystart, img.height, rotated_height):
            for xpos in range(xstart, img.width, int(density_x)):
                print((xpos, ypos), watermark_text)
                txt_layer.paste(rotated_watermark, (xpos, ypos), rotated_watermark)

        img.paste(txt_layer, (0, 0), txt_layer)

        original_filename = os.path.basename(image_path)
        name, extension = os.path.splitext(original_filename)
        new_filename = f"{name}(水印)"

        if (original_mode == "RGB"):
            img = img.convert('RGB')
            if img.mode == "RGBA":
                print("\n转换失败")
                print(f"\nimg size: {img.size}, mode: {img.mode}")
            pass

        while True:   
            try:
                save_file(img, f"{new_filename}{extension}", extension)
                messagebox.showinfo("提示", "图片已保存")
                return
            except:
                response = messagebox.askyesnocancel ("保存失败", "如果保存jpg失败的话请尝试保存成png\n是否为你改为png？，\n点击取消放弃保存")
                if response is True:
                    extension = ".png"
                    continue
                elif response is False:
                    continue
                else:
                    print("\n用户选择放弃保存")
                    return

add_tiled_watermark()
