QRcode Perl CGI & PHP script ver.0.50i

                                 Copyright (c)2000-2009, Y.Swetake
                                 All Rights Reserved.

1, about this software

This is a free software to output a image of QRcode on Perl or PHP.
These programs support QRcode model2 version1-40,and some functions
are NOT supported. (eg. mode change,KANJI mode etc.)



2,directory & files 

  qr_img0.50-+-perl--+- qr_img.cgi
             |       +- qr_image.pl
             |       +- qr_html.pl
             |
             +-data -+- qrvV_N.dat
             |       +- rscX.dat
             |       +- qrvfrV.dat
             |
             +-image-+- qrvV.png
             |       +- b.png d.png
             |       
             +-php  -- qr_img.php


qr_img.cgi    Perl program (CGI program ,but this runs on shell,too.)
qr_image.pl   sub program for output a png or jpeg image.
qr_html.pl    sub program for output html.

qrvV_N.dat    data file of geometry & mask for version V ,ecc level N
rscX.dat      data file of caluclatin tables for RS encoding
qrvfrV.dat    data file of fixed pattern for version V (for html mode)

qrvV.png      image file of fixed pattern for version V.
b.png         bright square image (for html mode)
d.png         dark square image (for html mode)

qr_img.php    PHP program (requires GD.)

README.txt    document in Japanese (EUC)
README.sjis   document in Japanese (SJIS)
README_e.txt  this document.



3,requirement

 If you create a PNG or JPEG image,you need GD.
 And you may need to compile or to coordinate parameters
 for using GD correctly from Perl or PHP.

 I checked this program on below enviroment

     Linux 2.4.18 (x86)
　　 apache-1.3.27 + PHP-4.3.0(as apache module)
　　 perl 5.6.1
　　 GD 2.0.11
　　 GD.pm 2.06

* CAUTION *
  This program do NOT run on GD 2.0.[0-9] or PHP4.3.[01] bundle
  for GD's bug.
  Please use GD version 1.8.x , 2.0.10 or above.



4,usage

4-1,setup

Set a path to perl or php(in using as cgi).

Change values in setting area if you need.
(If you use in unpacked placement,you don't have to change value.
But you may need to move some files to indicated position.
eg. b.png )


4-2,usage

From browser

qr_img.cgi?d=data[&e=(L,M,Q,H)][&s=int size][&v=(1-40)][&t=(J,H)]
          [&m=(1-16)&n=(2-16)[&o=original data][&p=(0-255)]]

qr_img.php?d=data[&e=(L,M,Q,H)][&s=int size][&v=(1-40)][&t=J]
          [&m=(1-16)&n=(2-16)[&o=original data][&p=(0-255)]]

d : Data you want to encode to QRcode.
    A special letter like '%'.space or 8bit letter must be URL-encoded.
    You cannot omit this parameter.

e : Error correct level
    You can set 'L','M','Q' or 'H'.

    If you don't set,program selects 'M'.

s : module size
    This parameter is no effect in HTML mode.
    You can set a number more than 1.
    Image size depends on this parameter.

    If you don't set,program selects '4' in PNG mode or '8' in JPEG mode.

v : version
    You can set 1-40.
    If you don't set,program automatically selects.

t : image type
    You can set 'J','H' or other.
    'J' : jpeg mode.
    'H' : html mode.(for perl only)
    Other : png mode.

    If you don't set,program select PNG mode.


* CAUTION *
below parameter is experimental.

n : structure append n (2-16)  image No. m of n.
m : structure append m (1-16)  image No. m of n.
p : structure append parity
o : original data (URL-encoded data)  for calculating parity



From command line (for perl only)

example
 $ ./qr_img.cgi d=This+is+a+pen e=L s=3 > qrcode.png

 $ ./qr_img.cgi e=H < data.txt > qrcode.png

 If you input data from STDIN,data don't have to be URL-encoded.



5,Notice

This software is a free software.
You can freely use,change or redistribute unless you change the 
copyright and disclaimer in this program and this document.


THIS SOFTWARE IS PROVIDED BY Y.Swetake ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL Y.Swetake OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED 
AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


6,Others

If you find bugs,please tell me.
(But I may be unable to reply,because I'm a poor at English.)

e-mail: swe[ at-mark ]venus.dti.ne.jp

URL: http://www.swetake.com/ (Japanese page)


