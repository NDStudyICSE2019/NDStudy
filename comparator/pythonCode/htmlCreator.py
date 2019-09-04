
from jinja2 import Environment, FileSystemLoader
import os
import shutil
import json
import sys
from datetime import datetime
from pythonDBCreator import connectToDB, closeDBConnection, SCREENSHOTS,  fetchRandomNearDuplicates
from createNearDuplicateImages import createNDImage
from importResponses import importJson

# # Capture our current directory
# THIS_DIR = os.path.dirname(os.path.abspath(__file__))

def output_html_doc(templatedir, templateHtml, destination, jsonData, Title='Near Duplicate Detection', saveJsonName=None
	,preLoadedBins = None):
	# Create the jinja2 environment.
	# Notice the use of trim_blocks, which greatly helps control whitespace.
	j2_env = Environment(loader=FileSystemLoader(templatedir),
							trim_blocks=True)
	
	output = ''
	print(saveJsonName)
	if(preLoadedBins ==None) :
		if saveJsonName == None :
			output = j2_env.get_template(templateHtml).render(
				title=Title, jsonData=jsonData)
		else:
			print("outputting with save json name")
			output = j2_env.get_template(templateHtml).render(
			title=Title, jsonData=jsonData, saveJsonName = saveJsonName)
	else:
		if saveJsonName == None :
			output = j2_env.get_template(templateHtml).render(
				title=Title, jsonData=jsonData, preLoadedBins = preLoadedBins)
		else:
			print("outputting with save json name")
			output = j2_env.get_template(templateHtml).render(
			title=Title, jsonData=jsonData, saveJsonName = saveJsonName, preLoadedBins = preLoadedBins)
		
	#print(output)

	with open(destination, "w") as f:
		f.write(output) 


def searchImageInCrawlPath(appName, crawl, imageName, searchPath):
	if os.path.exists(os.path.join(searchPath, appName, crawl, SCREENSHOTS, imageName)):
		print("FOUND IN IMMEDIATE SUBDIRECTORY {0}".format(appName) )
		return os.path.join(searchPath, appName, crawl, SCREENSHOTS, imageName)
	directories = [x for x in os.listdir(searchPath) if os.path.isdir(os.path.join(searchPath, x))]
	# print(directories)
	file_found = 0
	for directory in directories:
		# print(directory)
		filename = os.path.join(searchPath, directory, appName, crawl, SCREENSHOTS, imageName)
		# print(filename)
		if os.path.exists(filename):
			file_found = 1
			break
	if file_found:
		return filename
	else :
		print("IMAGE MISSING IN THE SEARCH PATH : "+ os.path.join( appName, crawl,  imageName ))



###########################################################################
## CommandLine Argument Validation ############
###########################################################################

def confirmOutputPath(outputPath, resources, overwrite=None):
	abortNum = 0
	MAX_RETRY_NUM = 3
	outputPathConfirmed = False
	while not outputPathConfirmed:
		if(os.path.exists(outputPath)) :
			print("OUTPUT PATH ALREADY EXISTS")
			response = None
			if overwrite ==None :
				response = input("DO you want to overwrite? (Y/N)").strip().lower()
			else:
				if overwrite:
					response = 'y'
				else:
					response = 'n'

			if(response=='y'):
				print("okay overwriting.")
				outputPathConfirmed = True
			else:
				if(abortNum >= MAX_RETRY_NUM):
					print("EXCEEDED MAX RETRY!! ")
					return False

				outputPath = os.path.join(os.path.abspath(input("Provide a new output path.").strip()), '')
				abortNum += 1

		else:	
		# 	os.makedirs(os.path.dirname(OUTPUT_PATH), exist_ok=True)
		# 	#os.makedirs(os.path.dirname(OUTPUT_PATH + IMAGES), exist_ok=True)
			print("CREATING OUTPUT PATH {0}".format(outputPath))
			shutil.copytree(resources, outputPath)
			outputPathConfirmed = True	

	return outputPathConfirmed, outputPath

###########################################################################
## Tests ############
###########################################################################
def testJinjaCreator():

	data = [
	{"app":"testapp", "crawl":"testcrawl", "state1": "index", "state2": "state2", "image":"images/testImage1.jpg", "response":-1},
	{"app":"testapp2", "crawl":"testcrawl2", "state1" : "state110", "state2" : "state220", "image":"images/testImage2.jpg", "response" : -1}
	]
	jsonData= json.dumps(data)

	output_html_doc("/comparator/HTMLStuff", "/comparator/HTMLStuff/testJinja.html", jsonData)


def testMultiFolder():
	searchPath = '/Test/'
	appName = 'fabrick.me'
	crawl = 'crawl0'
	imageName = 'index.png'
	print(searchImageInCrawlPath(appName, crawl, imageName, searchPath))

#testMultiFolder()



###########################################################################
## Main Code ############
###########################################################################

def randomPairOuput():
	DB_PATH = "/gt10_Doms/"
	DB_NAME = "DS.db"
	CRAWL_PATH = "/gt10_Doms/"
	RESOURCES = os.path.join(os.path.abspath("/state-abstraction-study/HTMLStuff/resources/"), '')
	TEMPLATE_DIR = os.path.join(os.path.abspath("/state-abstraction-study/HTMLStuff/"), '')
	TEMPLATE_HTML = "jinjaTemplate.html"
	timeString = str(datetime.now().strftime("%Y%m%d-%H%M%S"))
	NUMBER = 500
	OUTPUT_PATH = os.path.join(os.path.abspath("/htmloutputs/"), "htmloutput_"+ str(NUMBER) + "_"+timeString)
	OUTPUT_HTML_NAME = "randomPairOutput.html"
	IMAGES = "images"
	
	saveJsonName = "responseResults_"+ str(NUMBER) + "_"+ timeString + ".json"
	
	if len(sys.argv) <= 6 :
		print("You have not provided ENOUGH arguments. ")
		print("USAGE : program <DB_PATH> <DB_NAME> <CRAWL_PATH> <OUTPUT_PATH> <OUTPUT_HTML_NAME> <NUMBEROFPAIRS>")
		print("DO you want to use defaults DB : {0}, CRAWLS : {1}, OutputPath: {2}, OutputName : {3} and Number : {4} ?".format(DB_PATH + DB_NAME, CRAWL_PATH, OUTPUT_PATH, OUTPUT_HTML_NAME, NUMBER))
		response = input("Y/N : ").strip().lower()
		if(response == 'y'):
			print('Okay. Continuing with Defaults : Your output will be available at : ' + OUTPUT_PATH)
		else:
			DB_PATH = input("DB_PATH:").strip()
			DB_NAME = input("DB_NAME:").strip()
			CRAWL_PATH = input("CRAWL_PATH:").strip()
			OUTPUT_PATH = input("OUTPUT_PATH:").strip()
			OUTPUT_HTML_NAME = input("OUTPUT_HTML_NAME:").strip()
			MAX_RETRY_NUM = 3
			numberNotConfirmed = True
			retryNum = 0
			while numberNotConfirmed:
				try:
					NUMBER = int(input("NUMBER_OF_PAIRS:").strip())
					break
				except Exception as e:
					print(e)
					if(retryNum>=MAX_RETRY_NUM):
						print("EXCEEDED MAX RETRY. ABORTING!! ")
						print("USAGE : program <DB_PATH> <DB_NAME> <CRAWL_PATH> <OUTPUT_PATH> <OUTPUT_HTML_NAME> <NUMBEROFPAIRS>")
						sys.exit()
					retryNum +=1
					print("Please provide a valid number.")


	elif len(sys.argv) == 7:
		DB_PATH = sys.argv[1]
		DB_NAME = sys.argv[2]
		CRAWL_PATH = sys.argv[3]
		OUTPUT_PATH = sys.argv[4]
		OUTPUT_HTML_NAME = sys.argv[5]
		NUMBER = int(sys.argv[6])

	
	DB_PATH = os.path.join(os.path.abspath(DB_PATH.strip()), '')
	CRAWL_PATH = os.path.join(os.path.abspath(CRAWL_PATH.strip()), '')
	OUTPUT_PATH =os.path.join(os.path.abspath(OUTPUT_PATH.strip()), '')

	print("USING THESE VALUES FOR PROGRAM: DB : {0}, CRAWLS : {1}, OutputPath: {2}, OutputName : {3} and Number : {4} ?".format(DB_PATH + DB_NAME, CRAWL_PATH, OUTPUT_PATH, OUTPUT_HTML_NAME, NUMBER))
	response = input("Continue ? Y/N : ").strip().lower()
	if response=='y' :
		print("Okay. Continuing!!")
	else : 
		print("ABORTING!! TRY AGAIN")
		print("USAGE : program <DB_PATH> <DB_NAME> <CRAWL_PATH> <OUTPUT_PATH> <OUTPUT_HTML_NAME> <NUMBEROFPAIRS>")
		sys.exit()


	if not os.path.exists(DB_PATH+ DB_NAME) :
		print("DB DOES NOT EXIST. ABORTING!!")
		sys.exit()
	
	if not os.path.exists(CRAWL_PATH):
		print("CRAWL PATH DOES NOT EXIST. ABORTING")
		sys.exit()

	if not os.path.exists(RESOURCES):
		print("RESOURCES NOT FOUND AT : {0} ".format(RESOURCES))
		RESOURCES = os.path.join(os.path.abspath(input("Provide RESOURCES FOLDER.").strip()), '')
		if not os.path.exists(RESOURCES):
			print("PROVIDED RESOURCES PATH DOES NOT EXIST : {0}. ABORTING!!".format(RESOURCES))
			sys.exit()

	if not os.path.exists(TEMPLATE_DIR + TEMPLATE_HTML) :
		print("TEMPALTE {0} NOT FOUND AT : {1} ".format(TEMPLATE_HTML, TEMPLATE_DIR))
		TEMPLATE_DIR = os.path.join(os.path.abspath(input("Provide FOLDER where TEMPLATE HTML IS.").strip()), '')
		if not os.path.exists(TEMPLATE_DIR + TEMPLATE_HTML):
			print("TEMPALTE {0} NOT FOUND AT : {1} ".format(TEMPLATE_HTML, TEMPLATE_DIR))
			TEMPLATE_HTML = input("Provide HTML Template to use.").strip()
			if not os.path.exists(RESOURCES + TEMPLATE_HTML):
				print("TEMPALTE {0} NOT FOUND AT : {1} . ABORTING !!".format(TEMPLATE_HTML, TEMPLATE_DIR))
				sys.exit()
	else:
		print("USING TEMPLATE {0} at {1}".format(TEMPLATE_HTML, TEMPLATE_DIR))

	
	outputPathConfirmed, OUTPUT_PATH = confirmOutputPath(OUTPUT_PATH, RESOURCES)
	if not outputPathConfirmed:
		print("USAGE : program <DB_PATH> <DB_NAME> <CRAWL_PATH> <OUTPUT_PATH> <OUTPUT_HTML_NAME> <NUMBEROFPAIRS>")
		sys.exit()

	connectToDB(DB_PATH + DB_NAME)
	#randomNDs = fetchRandomNearDuplicates(NUMBER*2)
	# randomNDs = fetchRandomNearDuplicates(NUMBER*2, "appname in (select name from apps where numAddedStates>=10) AND ")
	randomNDs = fetchRandomNearDuplicates(NUMBER*2, "HUMAN_CLASSIFICATION==-1 AND ")
	closeDBConnection()
	data = []
	numCreated = 0
	missing = 0
	for randND in randomNDs:
		jsonRecord = {}
		appName = randND[0]
		crawl = randND[1]
		state1 = randND[2]
		state2 = randND[3]

		jsonRecord['appname'] = appName
		jsonRecord['crawl'] = crawl
		jsonRecord['state1'] = state1
		jsonRecord['state2'] = state2
		image1Name = state1 + ".png"
		image2Name = state2 + ".png"
		image1 = searchImageInCrawlPath(appName, crawl, image1Name, CRAWL_PATH)
		image2 = searchImageInCrawlPath(appName, crawl, image2Name, CRAWL_PATH)
		if(image1 == None ) or (image2 == None):
			print("IMAGES NOT FOUND ABORTING THIS PAIR :  " + str(jsonRecord)) 
			missing +=1 
			continue
		ndImageName = appName + "_" + crawl + "_" + state1 + "_" + state2 + ".jpg"
		destination = OUTPUT_PATH+IMAGES+"/" +ndImageName
		if createNDImage(image1, image2, destination ):
			numCreated +=1
		else:
			print("COULD NOT CREATE ND IMAGE FOR RECORD : " + str(jsonRecord) )
			
		jsonRecord['image'] = ndImageName
		jsonRecord['response'] = randND[14]
		jsonRecord['tags'] = ""
		jsonRecord['comments'] = ""
		data.append(jsonRecord)

		if(numCreated >= NUMBER):
			break


	jsonData= json.dumps(data)

	TEMPLATE_HTML_PATH = os.path.abspath(os.path.join(TEMPLATE_DIR, TEMPLATE_HTML))
	print(jsonData)
	output_html_doc(TEMPLATE_DIR, TEMPLATE_HTML, OUTPUT_PATH + OUTPUT_HTML_NAME, jsonData, saveJsonName=saveJsonName)
	print("FAILED CREATING {0} PAIRS BECAUSE IMAGES WERE MISSING".format(missing))
	print("OUTPUT HTML CREATED WITH : {0} IMAGE PAIRS".format(numCreated))

def singleCrawlOutput(
	CRAWL_PATH 
	,OUTPUT_PATH 
	,OUTPUT_HTML_NAME
	,TITLE
	,outputJson = None
	,saveJsonName = "gsResults.json"
	,preLoadedBins = None
	,overwrite = None
	,RESOURCES = os.path.join(os.path.abspath("../HTMLStuff/resources/"), '')
	,TEMPLATE_DIR = os.path.join(os.path.abspath("../HTMLStuff/"), '')
	,TEMPLATE_HTML = "gsTemplate.html"
	):
	
	TEMPLATE_HTML_PATH = os.path.abspath(os.path.join(TEMPLATE_DIR, TEMPLATE_HTML))
	IMAGES = "images"
	SCREENSHOTS = "screenshots"
	CRAWL_RESULT_JSON_FILE = os.path.abspath(os.path.join(CRAWL_PATH, "result.json"))

	outputPathConfirmed, OUTPUT_PATH = confirmOutputPath(OUTPUT_PATH, RESOURCES, overwrite = overwrite)
	if not outputPathConfirmed:
		# print("USAGE : program <DB_PATH> <DB_NAME> <CRAWL_PATH> <OUTPUT_PATH> <OUTPUT_HTML_NAME> <NUMBEROFPAIRS>")
		return


	if outputJson!=None:
		outputJsonString = json.dumps(outputJson)
		output_html_doc(TEMPLATE_DIR, TEMPLATE_HTML, os.path.join(OUTPUT_PATH, OUTPUT_HTML_NAME), outputJsonString, TITLE, saveJsonName = saveJsonName, preLoadedBins = preLoadedBins)

		return

	resultJson = importJson(CRAWL_RESULT_JSON_FILE)
	states= resultJson['states']
	outputJson = {'states':{}, 'pairs':[]}
	sortedStates = sorted(states.items() ,  key=lambda x: x[1]['id'])


	for state1 in sortedStates:
		# print(state1[0])
		i = state1[1]['id']
		stateOutput={}
		stateOutput['name'] = state1[1]['name']
		stateOutput['bin']=""
		stateOutput['clones']=[]
		stateOutput['id']=i
		stateOutput['url']=state1[1]['url']
		if 'timeAdded' in state1[1]:
			stateOutput['timeAdded']=state1[1]['timeAdded']
		else:
			stateOutput['timeAdded']=i

		if 'url' in state1[1]:
			stateOutput['url'] = state1[1]['url']

		# print(stateOutput)
		outputJson['states'][state1[0]] = stateOutput
		for state2 in sortedStates:
			j = state2[1]['id']
			if(j<=i):
				continue
			pair = {}
			pair['state1'] = state1[0]
			pair['state2'] = state2[0]
			pair['response'] = -1
			pair['inferred'] = 0
			pair['tags']=[]
			pair['comments']=""
			outputJson['pairs'].append(pair)
			# print(pair)

	outputJsonString = json.dumps(outputJson)
	print(outputJsonString)
	output_html_doc(TEMPLATE_DIR, TEMPLATE_HTML, os.path.join(OUTPUT_PATH, OUTPUT_HTML_NAME), outputJsonString, TITLE, saveJsonName = saveJsonName, preLoadedBins = preLoadedBins)



if __name__ == '__main__':
	# print("Hello")
	# randomPairOuput();
	# CRAWL_PATH = "/comparator/src/main/resources/GoldStandards/Petclinic/crawl-petclinic-60min_old"
	# CRAWL_PATH = "/comparator/src/main/resources/GoldStandards/Addressbook/crawl-addressbook-60min"

	# CRAWL_PATH = "/comparator/src/main/resources/GoldStandards/Claroline/crawl-claroline-60min"
	# CRAWL_PATH = "/comparator/src/main/resources/GoldStandards/mantisbt/crawl-mantisbt-60min"
	# CRAWL_PATH = "/testBatch/pagekit/crawl0"
	CRAWL_PATH = "/comparator/src/main/resources/GoldStandards/pagekit/crawl-pagekit-60min"

	OUTPUT_PATH = os.path.join(os.path.abspath(CRAWL_PATH), "gs")
	OUTPUT_HTML_NAME = "gs.html"
	TITLE = "GoldStandard"
	singleCrawlOutput(CRAWL_PATH, OUTPUT_PATH, OUTPUT_HTML_NAME, TITLE)
	#