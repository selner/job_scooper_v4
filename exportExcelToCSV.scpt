FasdUAS 1.101.10   ��   ��    k             l     ����  I     �������� 00 exportbryanjobtracking ExportBryanJobTracking��  ��  ��  ��      	  l     ��������  ��  ��   	  
  
 i         I      �������� 0 	runwithui 	runWithUI��  ��    k            l     ��������  ��  ��        l     ��  ��    C = Get the filename of the Excel Spreadsheet we're going to use     �   z   G e t   t h e   f i l e n a m e   o f   t h e   E x c e l   S p r e a d s h e e t   w e ' r e   g o i n g   t o   u s e      r     	    l     ����  I    ���� 
�� .sysostdfalis    ��� null��    �� ��
�� 
prmp  m       �   P S e l e c t   t h e   E x c e l   S p r e a d s h e e t   t o   C o n v e r t :��  ��  ��    o      ���� 0 thefile theFile       l  
 
��������  ��  ��      ! " ! l  
 
�� # $��   # 4 . The directory that will contain our CSV files    $ � % % \   T h e   d i r e c t o r y   t h a t   w i l l   c o n t a i n   o u r   C S V   f i l e s "  & ' & r   
  ( ) ( l  
  *���� * I  
 ���� +
�� .sysostflalis    ��� null��   + �� ,��
�� 
prmp , m     - - � . . 6 S e l e c t   F o l d e r   t o   O u t p u t   T o :��  ��  ��   ) o      ���� "0 outputdirectory outputDirectory '  / 0 / l   ��������  ��  ��   0  1 2 1 I    �� 3����  0 exportxlstocsv ExportXLStoCSV 3  4 5 4 o    ���� 0 thefile theFile 5  6 7 6 o    ���� "0 outputdirectory outputDirectory 7  8�� 8 m     9 9 � : :  ��  ��   2  ;�� ; l   ��������  ��  ��  ��     < = < l     ��������  ��  ��   =  > ? > i     @ A @ I      �������� 00 exportbryanjobtracking ExportBryanJobTracking��  ��   A I     	�� B����  0 exportxlstocsv ExportXLStoCSV B  C D C m     E E � F F � / U s e r s / b r y a n / D r o p b o x / J o b   S e a r c h   2 0 1 3 / C o m p a n y   R e s e a r c h / A M Z N J o b s - B r y a n - T r a c k i n g - L i s t . x l s x D  G H G m     I I � J J , U s e r s : b r y a n : C o d e : d a t a : H  K L K m     M M � N N  J o b s L i s t L  O�� O m     P P � Q Q 0 b r y a n s _ c u r r e n t _ j o b s _ l i s t��  ��   ?  R S R l     ��������  ��  ��   S  T�� T i     U V U I      �� W����  0 exportxlstocsv ExportXLStoCSV W  X Y X o      ���� 0 	inputfile 	inputFile Y  Z [ Z o      ���� 0 	outputdir 	outputDir [  \ ] \ o      ���� 0 strsheetname strSheetName ]  ^�� ^ o      ����  0 stroutfilename strOutFileName��  ��   V k     � _ _  ` a ` l     ��������  ��  ��   a  b c b l     �� d e��   d %  Excel Spreadsheet to CSV Files    e � f f >   E x c e l   S p r e a d s h e e t   t o   C S V   F i l e s c  g h g l     �� i j��   i %  by Scott Wilcox <scott@dor.ky>    j � k k >   b y   S c o t t   W i l c o x   < s c o t t @ d o r . k y > h  l m l l     �� n o��   n / ) https://github.com/dordotky/applescripts    o � p p R   h t t p s : / / g i t h u b . c o m / d o r d o t k y / a p p l e s c r i p t s m  q r q l     ��������  ��  ��   r  s t s O     � u v u k    � w w  x y x l   �� z {��   z   Get Excel to activate    { � | | ,   G e t   E x c e l   t o   a c t i v a t e y  } ~ } I   	������
�� .miscactvnull��� ��� null��  ��   ~   �  l  
 
��������  ��  ��   �  � � � l  
 
�� � ���   � , & Close any workbooks that we have open    � � � � L   C l o s e   a n y   w o r k b o o k s   t h a t   w e   h a v e   o p e n �  � � � I  
 �� ���
�� .coreclosnull���    obj  � 2  
 ��
�� 
X141��   �  � � � l   ��������  ��  ��   �  � � � l   �� � ���   � 0 * Ask Excel to open the theFile spreadsheet    � � � � T   A s k   E x c e l   t o   o p e n   t h e   t h e F i l e   s p r e a d s h e e t �  � � � I   �� ���
�� .aevtodocnull  �    alis � o    ���� 0 	inputfile 	inputFile��   �  � � � l   �� � ���   � B < Set maxCount to the total number of sheets in this workbook    � � � � x   S e t   m a x C o u n t   t o   t h e   t o t a l   n u m b e r   o f   s h e e t s   i n   t h i s   w o r k b o o k �  � � � r    # � � � I   !�� ���
�� .corecnte****       **** � n     � � � 2   ��
�� 
XwSH � 1    ��
�� 
1172��   � o      ���� 0 maxcount maxCount �  � � � l  $ $��������  ��  ��   �  � � � l  $ $�� � ���   � C = For each sheet in the workbook, loop through then one by one    � � � � z   F o r   e a c h   s h e e t   i n   t h e   w o r k b o o k ,   l o o p   t h r o u g h   t h e n   o n e   b y   o n e �  � � � Y   $ � ��� � ��� � k   . � � �  � � � Z   . = � ����� � ?   . 1 � � � o   . /���� 0 i   � m   / 0����  � I  4 9�� ���
�� .aevtodocnull  �    alis � o   4 5���� 0 	inputfile 	inputFile��  ��  ��   �  � � � l  > >��������  ��  ��   �  � � � l  > >�� � ���   � 5 / Set the current worksheet to our loop position    � � � � ^   S e t   t h e   c u r r e n t   w o r k s h e e t   t o   o u r   l o o p   p o s i t i o n �  � � � r   > H � � � n   > F � � � 1   D F��
�� 
pnam � n   > D � � � 4   A D�� �
�� 
XwSH � o   B C���� 0 i   � 1   > A��
�� 
1172 � o      ���� $0 theworksheetname theWorksheetname �  � � � Z   I � � ����� � l  I T ����� � G   I T � � � l  I L ����� � =   I L � � � o   I J���� 0 strsheetname strSheetName � o   J K�� $0 theworksheetname theWorksheetname��  ��   � =  O R � � � o   O P�~�~ 0 strsheetname strSheetName � m   P Q � � � � �  ��  ��   � k   W � � �  � � � l  W W�}�|�{�}  �|  �{   �  � � � r   W _ � � � n   W ] � � � 4   Z ]�z �
�z 
XwSH � o   [ \�y�y 0 i   � 1   W Z�x
�x 
1172 � o      �w�w 0 theworksheet theWorksheet �  � � � I  ` e�v ��u
�v .smXLxACTnull���   6 4001 � o   ` a�t�t 0 theworksheet theWorksheet�u   �  � � � l  f f�s�r�q�s  �r  �q   �  � � � l  f f�p � ��p   � ' ! Save the worksheet as a CSV file    � � � � B   S a v e   t h e   w o r k s h e e t   a s   a   C S V   f i l e �  � � � r   f o � � � c   f m � � � b   f k � � � b   f i � � � o   f g�o�o 0 	outputdir 	outputDir � o   g h�n�n  0 stroutfilename strOutFileName � m   i j � � � � �  . c s v � m   k l�m
�m 
TEXT � o      �l�l 0 thesheetspath theSheetsPath �  ��k � I  p ��j � �
�j .smXL1659null���   7 X128 � o   p q�i�i 0 theworksheet theWorksheet � �h � �
�h 
5016 � o   r s�g�g 0 thesheetspath theSheetsPath � �f � �
�f 
1813 � m   t w�e
�e e188�  � �d ��c
�d 
5321 � m   z {�b
�b boovtrue�c  �k  ��  ��   �  � � � l  � ��a�`�_�a  �`  �_   �  � � � l  � ��^ � ��^   � 2 , Close the worksheet that we've just created    � � � � X   C l o s e   t h e   w o r k s h e e t   t h a t   w e ' v e   j u s t   c r e a t e d �  �]  I  � ��\
�\ .coreclosnull���    obj  1   � ��[
�[ 
1172 �Z�Y
�Z 
savo m   � ��X
�X savono  �Y  �]  �� 0 i   � m   ' (�W�W  � o   ( )�V�V 0 maxcount maxCount��   �  l  � ��U�T�S�U  �T  �S    l  � ��R	�R     Clean up and close files   	 �

 2   C l e a n   u p   a n d   c l o s e   f i l e s �Q I  � ��P�O
�P .coreclosnull���    obj  2  � ��N
�N 
X141�O  �Q   v m     
                                                                                  XCEL  alis    �  Macintosh HD               ��ҨH+   8��Microsoft Excel.app                                             8�uȚW�        ����  	                Microsoft Office 2011     ��C(      Ț�5     8��   `  EMacintosh HD:Applications: Microsoft Office 2011: Microsoft Excel.app   (  M i c r o s o f t   E x c e l . a p p    M a c i n t o s h   H D  6Applications/Microsoft Office 2011/Microsoft Excel.app  / ��   t �M l  � ��L�K�J�L  �K  �J  �M  ��       �I�I   �H�G�F�E�H 0 	runwithui 	runWithUI�G 00 exportbryanjobtracking ExportBryanJobTracking�F  0 exportxlstocsv ExportXLStoCSV
�E .aevtoappnull  �   � **** �D �C�B�A�D 0 	runwithui 	runWithUI�C  �B   �@�?�@ 0 thefile theFile�? "0 outputdirectory outputDirectory �> �= -�< 9�;
�> 
prmp
�= .sysostdfalis    ��� null
�< .sysostflalis    ��� null�;  0 exportxlstocsv ExportXLStoCSV�A *��l E�O*��l E�O*���m+ OP �: A�9�8�7�: 00 exportbryanjobtracking ExportBryanJobTracking�9  �8      E I M P�6�5�6 �5  0 exportxlstocsv ExportXLStoCSV�7 
*�����+  �4 V�3�2�1�4  0 exportxlstocsv ExportXLStoCSV�3 �0�0   �/�.�-�,�/ 0 	inputfile 	inputFile�. 0 	outputdir 	outputDir�- 0 strsheetname strSheetName�,  0 stroutfilename strOutFileName�2   	�+�*�)�(�'�&�%�$�#�+ 0 	inputfile 	inputFile�* 0 	outputdir 	outputDir�) 0 strsheetname strSheetName�(  0 stroutfilename strOutFileName�' 0 maxcount maxCount�& 0 i  �% $0 theworksheetname theWorksheetname�$ 0 theworksheet theWorksheet�# 0 thesheetspath theSheetsPath �"�!� ����� ��� ����������
�" .miscactvnull��� ��� null
�! 
X141
�  .coreclosnull���    obj 
� .aevtodocnull  �    alis
� 
1172
� 
XwSH
� .corecnte****       ****
� 
pnam
� 
bool
� .smXLxACTnull���   6 4001
� 
TEXT
� 
5016
� 
1813
� e188� 
� 
5321� 
� .smXL1659null���   7 X128
� 
savo
� savono  �1 �� �*j O*�-j O�j O*�,�-j E�O sk�kh �k 
�j Y hO*�,�/�,E�O�� 
 �� �& /*�,�/E�O�j O��%�%�&E�O���a a ea  Y hO*�,a a l [OY��O*�-j UOP ����
� .aevtoappnull  �   � **** k       ��  �  �     �
�
 00 exportbryanjobtracking ExportBryanJobTracking� *j+   ascr  ��ޭ