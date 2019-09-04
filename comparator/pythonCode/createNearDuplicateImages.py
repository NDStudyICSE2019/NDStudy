from PIL import Image
import sys

def createNDImage(image1, image2, destination):
	try:
		im1 = Image.open(image1)
		im2 = Image.open(image2)
		#images = map(Image.open, [image1, image2])
		im1width, im1height = im1.size
		im2width, im2height = im2.size
		#print(images.size)
		total_width = im1width + im2width + 20
		max_height = max(im1height, im2height)
		x_offset = 0
		new_im = Image.new('RGB', (total_width, max_height))
		new_im.paste(im1, (x_offset,0))
		x_offset += im1width + 20
		new_im.paste(im2, (x_offset,0))

		#for im in images:
		#  print('now here')
		#  new_im.paste(im, (x_offset,0))
		#  x_offset += im.size[0]


		new_im = new_im.resize((int(total_width*2/3), int(max_height*2/3)), Image.ANTIALIAS)
		new_im.save(destination, quality=40)
		return True
	except Exception as e:
		print(e)
		return False



if __name__ == '__main__':
	path = '/testBatch/01spz.com/crawl0/screenshots/'
	outputPath = '/testBatch/01spz.com/crawl0/nearDuplicates/'
	image1 = path + 'index.png'
	image2 = path + 'state2.png'
	im1 = Image.open(image1)
	im2 = Image.open(image2)
	#images = map(Image.open, [image1, image2])
	im1width, im1height = im1.size
	im2width, im2height = im2.size
	#print(images.size)
	total_width = im1width + im2width + 20
	max_height = max(im1height, im2height)
	x_offset = 0
	new_im = Image.new('RGB', (total_width, max_height))
	new_im.paste(im1, (x_offset,0))
	x_offset += im1width + 20
	new_im.paste(im2, (x_offset,0))

	#for im in images:
	#  print('now here')
	#  new_im.paste(im, (x_offset,0))
	#  x_offset += im.size[0]


	new_im = new_im.resize((int(total_width*2/3), int(max_height*2/3)), Image.ANTIALIAS)
	new_im.save(outputPath+ 'test.jpg', quality=40)

