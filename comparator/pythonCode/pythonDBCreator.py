from enum import Enum
from PIL import Image
import sqlite3
import os
import json
import fnmatch
import csv
import sys
from globalNames import DB_NAME, GS_DB_NAME, SCREENSHOTS, DOMS, STATES, COMP_OUTPUT, VERIFIED_CLASSIFICATION_JSON_NAME, GS_JSON_NAME, DISTANCES_RESPONSES_JSON, NODESIZES_JSON, PIXELSIZES_JSON, DOMSIZES_JSON, RESULT_JSON
from utils import importJson


###########################################################################
## string constants declaration ############
###########################################################################

TESTING_PHASE = True
CRAWL_PATH  = '/Test/gt10/'
LOGS_PATH = '/Test/logs/'

FINISHED_LOG = 'crawledFinishedFolders.log'
DB_PATH = CRAWL_PATH
DB = DB_PATH + DB_NAME
conn = 'noconnyet'
cursor = 'nocursoryet'

class ALGOS(Enum):
	 DOM_RTED= ['DOM-RTED', 0.0, 'lt']
	 DOM_Levenshtein = ['DOM-Levenshtein', 0.1, 'lt']
	 DOM_contentHash = ['DOM-contentHash', 100, 'lt']
	 DOM_SIMHASH = ['DOM-SIMHASH', 10, 'lt']
	 VISUAL_BLOCKHASH = ['VISUAL-BlockHash', 23.4, 'lt'] # 234 max
	 VISUAL_PHASH = ["VISUAL-PHash", 5.7, 'lt'] #57 max
	 VISUAL_HYST = ["VISUAL-Hyst", 100, 'lt'] # max 1.085430529912071E13
	 VISUAL_PDIFF = ["VISUAL-PDiff", 0.1, 'lt']
	 VISUAL_SIFT = ["VISUAL-SIFT", 90.0, 'gt']
	 VISUAL_SSIM = ["VISUAL-SSIM", 1.0, 'gt']
# for algo_name in ALGO_NAMES:
# 	print(algo_name.value)


###########################################################################
## SQLITE OPERATIONS ############
#####_######################################################################

def connectToDB(db):
	global conn
	global cursor

	if(conn=='noconnyet' or cursor =='nocursoryet'):
		print("CONNECTING TO DB ")
		try:
			conn= sqlite3.connect(db)
			cursor = conn.cursor()
			print('SUCCESSFULLY CONNECTED TO : ' + db)
		except Exception as e:
			print("ERROR CONNECTING TO DB !!!")
			print(e)
	return conn, cursor

def createTables():
	global conn
	global cursor

	try:
		cursor.execute(''' CREATE TABLE IF NOT EXISTS apps (
					name text,
					crawl text,
					numAddedStates int,
					numCrawledStates int,
					crawlTime real,
					PRIMARY KEY(name, crawl)
					)''')

		cursor.execute(''' CREATE TABLE IF NOT EXISTS nearduplicates(
					appname text,
					crawl text, 
					state1 text, 
					state2 text, 
					DOM_RTED real,
					DOM_Levenshtein real,
					DOM_contentHash real, 
					DOM_SIMHASH real,
					VISUAL_BlockHash real, 
					VISUAL_PHash real,
					VISUAL_Hyst real, 
					VISUAL_PDiff real,
					VISUAL_SIFT real,
					VISUAL_SSIM real,
					HUMAN_CLASSIFICATION int,
					TAGS text,
					FOREIGN KEY(appname, crawl) REFERENCES apps(name, crawl),
					PRIMARY KEY(appname, crawl, state1, state2)
					)''')

		cursor.execute(''' CREATE TABLE IF NOT EXISTS states(
						appname text,
						crawl text, 
						state text,
						url text,
						domSize int, 
						strippedDomSize int, 
						domStructureSize int, 
						domContentSize int, 
						nodeSize int, 
						pixelSize int,
						FOREIGN KEY(appname, crawl) REFERENCES apps(name, crawl),
						PRIMARY KEY(appname, crawl, state)
						)''')

		conn.commit()
		print("SUCCESFULLY CREATED TABLES")
	except Exception as e:
		print('Error Creating Tables')
		print(e)


def closeDBConnection():
	global conn
	if(conn == 'noconnyet'):
		print("NO DB CONNECTION ACTIVE")
	else:
		try:
			conn.commit()
			conn.close()
			print('SUCCESFULLY CLOSED DB CONNECTION')
			conn="noconnyet"
			cursor="nocursoryet"
		except:
			print("ERROR !! COULD NOT CLOSE CONNECTION")

## Adding Entries to database ############
def addCrawl(appName, crawl, numAddedStates, numCrawledStates, crawlTime):
	global conn
	global cursor
	addCrawlStatement = ('''INSERT INTO apps VALUES ( 
					 '{0}' ,
					 '{1}' , 
					 '{2}' ,
					 '{3}' , 
					 '{4}' 
					 )''').format(appName, crawl, numAddedStates, numCrawledStates, crawlTime)
	try: 
		cursor.execute(addCrawlStatement)
		conn.commit()
		return True
	except Exception as e:
		print("ERROR ADDING CRAWL : " + addCrawlStatement)
		print(e)
		return False

def updateCrawl(appName, crawl, numAddedStates, numCrawledStates, crawlTime):
	global conn
	global cursor
	updateCrawlStatement = ('''UPDATE apps 
								SET numAddedStates = {0},
								numCrawledStates = {1},
								crawlTime ={2} 
								WHERE name = '{3}' AND
								crawl = '{4}' 
							''').format( numAddedStates, numCrawledStates, crawlTime, appName, crawl)
	try: 
		cursor.execute(updateCrawlStatement)
		conn.commit()
		return True
	except Exception as e:
		print("ERROR UPDATING CRAWL : " + updateCrawlStatement)
		print(e)
		return False

## Adding Entries to database ############
def addNearDuplicate(appName, crawl, state1, state2):
	global conn
	global cursor

	initVal = -1


	addNDStatement = ('''INSERT INTO nearduplicates VALUES ( 
					 '{0}' ,
					 '{1}' , 
					 '{2}' ,
					 '{3}' , 
					 '{4}' ,
					 '{5}' ,
					 '{6}' ,
					 '{7}' ,
					 '{8}' ,
					 '{9}' ,
					 '{10}' ,
					 '{11}' ,
					 '{12}' ,
					 '{13}' ,
					 '{14}',
					 '{15}'
					 )''').format(appName, crawl, state1, state2, initVal, initVal,initVal, initVal, initVal, initVal, initVal, initVal, initVal, initVal, initVal, "")
	#print(addNDStatement)
	try: 
		cursor.execute(addNDStatement)
		conn.commit()
		return True
	except Exception as e:
		print("ERROR ADDING NEAR DUPLICATE : " + addNDStatement)
		print(e)
		return False

def resetDatabase():
	global conn
	global cursor
	try:
		cursor.execute(''' 
					DROP TABLE IF EXISTS apps
			''')

		cursor.execute(''' 
					DROP TABLE IF EXISTS nearduplicates
			''')
		createTables()
		print('DONE RESETTING THE DATABASE')
	except Exception as e:
		print('ERROR RESETTING THE DATABASE')
		print(e)


def checkCrawlEntryExists(appName, crawl):
	global cursor
	checkCrawlEntryStatement = ('''SELECT * FROM apps WHERE apps.name = '{0}'
								AND apps.crawl = '{1}'
								''').format(appName, crawl)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(checkCrawlEntryStatement)
		appentries = cursor.fetchall()
		if(len(appentries) == 0):
			return False
		else :
			return True
	except Exception as e:
		print("ERROR CHECKING FOR APP ENTRY : " +appName + '_' +  crawl)	
		print(e)						

def getCrawlRecord(appName, crawl):
	global cursor
	checkCrawlEntryStatement = ('''SELECT * FROM apps WHERE name = '{0}'
								AND crawl = '{1}'
								''').format(appName, crawl)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(checkCrawlEntryStatement)
		appentries = cursor.fetchall()
		return appentries
	except Exception as e:
		print("ERROR FETCHING RECORDS FOR APP ENTRY : " +appName + '_' +  crawl)	
		print(e)	
		return None

def checkNDEntryExists(appName, crawl, state1, state2):
	entries = fetchNearDuplicates(appName, crawl, state1, state2)
	if(len(entries) == 0):
		return False
	else:
		return True

def fetchCurrentNDAlgo(appName, crawl, state1, state2, algo):
	global conn
	global cursor
	checkNDEntryStatement = ('''SELECT {4} FROM nearduplicates WHERE appname = '{0}'
								AND crawl = '{1}'
								AND state1 = '{2}' 
								AND state2 = '{3}'
								''').format(appName, crawl, state1, state2, algo)
	# print(checkNDEntryStatement)
	try: 
		cursor.execute(checkNDEntryStatement)
		ndEntries = cursor.fetchall()
		return ndEntries
	except Exception as e:
		print("ERROR FETCHING ND ENTRY : {0} : {1} : {2} : {3} : {4}".format(appName, crawl, state1, state2, algo))	
		print(e)	
		return None					

def addState(appName, crawl, state, url):
	global conn
	global cursor

	initVal = -1
	domSize = strippedDomSize = domStructureSize = domContentSize = nodeSize = pixelSize = initVal

	addStateStatement = ('''INSERT INTO states VALUES ( 
					 '{0}' ,
					 '{1}' , 
					 '{2}' ,
					 '{3}' , 
					 '{4}' ,
					 '{5}' ,
					 '{6}' ,
					 '{7}' ,
					 '{8}' ,
					 '{9}'
					 )''').format(appName, crawl, state, url, domSize, strippedDomSize, domStructureSize, domContentSize, nodeSize, pixelSize)
	try: 
		cursor.execute(addStateStatement)
		conn.commit()
		return True
	except Exception as e:
		print("ERROR ADDING State : " + addStateStatement)
		print(e)
		return False

def fetchState(appName, crawl, state):
	global cursor
	fetchStateStatement = ('''SELECT * FROM states WHERE appname = '{0}'
								AND crawl = '{1}' AND state='{2}'
								''').format(appName, crawl, state)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(fetchStateStatement)
		stateentries = cursor.fetchall()
		return stateentries
	except Exception as e:
		print("ERROR FETCHING RECORDS FOR STATE ENTRY : " +appName + '_' +  crawl + ':' + state)	
		print(e)	
		return None


def updateState(appName, crawl, state, stateTuple, insertIfNotPresent=True):
	global cursor
	global conn

	Inserted = False
	Updated = False
	Ignored = False
	Error = False
	SameValue = False

	existingEntries = fetchState(appName, crawl, state)
	print(existingEntries)
	if (existingEntries == None) or (len(existingEntries)==0) :
		if insertIfNotPresent:
			print("NO SUCH ENTRY EXISTS. SO CREATING ONE NOW")
			addState(appName, crawl, state, "")
			Inserted = True
		else:
			print("Ignoring tuple not present in DB already")
			Ignored = True
			return Inserted, Updated, Ignored, SameValue, Error
			

	
	# if checkNDEntryExists(appName, crawl, state1, state2):
	# 	print("ENTRY EXISTS, SO CONTINUING WITH UPDATE")
	# else :
	setString = ' ,'.join([(value + '=' + str(stateTuple[value])) for value in stateTuple])

	updateStateEntryStatement = ('''UPDATE states SET {3}
								WHERE appname = '{0}'
								AND crawl = '{1}'
								AND state = '{2}' 
							''').format(appName, crawl, state, setString)
	try: 
		cursor.execute(updateStateEntryStatement)
		conn.commit()
		Updated = True
		return Inserted, Updated, Ignored, SameValue, Error
	except Exception as e:
		print("ERROR UPDATING STATE : " + updateStateEntryStatement)
		print(e)
		Error = True
		return Inserted, Updated, Ignored, SameValue, Error



def updateNearDuplicate(appName, crawl, state1, state2, algo, distance, insertIfNotPresent=True):
	global cursor
	global conn

	Inserted = False
	Updated = False
	Ignored = False
	Error = False
	SameValue = False
	existingEntries = fetchCurrentNDAlgo(appName, crawl, state1, state2, algo)
	
	if (existingEntries == None) or (len(existingEntries)==0) :
		if insertIfNotPresent:
			print("NO SUCH ENTRY EXISTS. SO CREATING ONE NOW")
			addNearDuplicate(appName, crawl, state1, state2)
			Inserted = True
		else:
			print("Ignoring tuple not present in DB already")
			Ignored = True
			return Inserted, Updated, Ignored, SameValue, Error
			

	else:
		#print(type(existingEntries[0][0]))
		if ((type(existingEntries[0][0]) is int) and existingEntries[0][0] == int(distance)) or ((type(existingEntries[0][0]) is float) and (existingEntries[0][0] == float(distance))):
			#print("No need to update. Existing entry has the same distance. ")
			SameValue = True
			return Inserted, Updated, Ignored, SameValue, Error
		else:
			print("New distance provided updating from {0} to {1}.".format(existingEntries[0][0], distance))
			
	# if checkNDEntryExists(appName, crawl, state1, state2):
	# 	print("ENTRY EXISTS, SO CONTINUING WITH UPDATE")
	# else :
		

	UpdateNDEntryStatement = ('''UPDATE nearduplicates SET {4} = {5}
								WHERE appname = '{0}'
								AND crawl = '{1}'
								AND state1 = '{2}' 
								AND state2 = '{3}'
							''').format(appName, crawl, state1, state2, algo, distance)
	# print(UpdateNDEntryStatement)
	try: 
		cursor.execute(UpdateNDEntryStatement)
		conn.commit()
		Updated = True
		return Inserted, Updated, Ignored, SameValue, Error
	except Exception as e:
		print("ERROR UPDATING NEAR DUPLICATE : " + UpdateNDEntryStatement)
		print(e)
		Error = True
		return Inserted, Updated, Ignored, SameValue, Error


def updateNearDuplicateMulti(tuples):
	global cursor
	global conn
	numTuplesAdded = 0
	failed = 0
	exists = 0
	created = 0
	for tup in tuples:
		appName = tup[0]
		crawl = tup[1]
		state1 = tup[2]
		state2 = tup[3]
		#print(tup)
		vals = tuples[tup]
		#print(vals)
		setString = ' ,'.join([(value + '=' + str(vals[value])) for value in vals])
		#print(setString)
		if(setString.strip() == ''):
			continue

		if checkNDEntryExists(appName, crawl, state1, state2):
			exists +=1
			# print("ENTRY EXISTS, SO CONTINUING WITH UPDATE")
		else :
			created += 1
			# print("NO SUCH ENTRY EXISTS. SO CREATING ONE NOW")
			addNearDuplicate(appName, crawl, state1, state2)
	
		UpdateNDEntryStatement = ('''UPDATE nearduplicates SET {4}
								WHERE appname = '{0}'
								AND crawl = '{1}'
								AND state1 = '{2}' 
								AND state2 = '{3}'
							''').format(appName, crawl, state1, state2, setString)

		try: 
			cursor.execute(UpdateNDEntryStatement)
			conn.commit()
			# print('UPDATED tuple' + UpdateNDEntryStatement)
			numTuplesAdded += 1
		except Exception as e:
			print("ERROR UPDATING NEAR DUPLICATE : " + UpdateNDEntryStatement)
			print(e)
			failed+=1

	return numTuplesAdded, failed


def fetchNearDuplicates(appName, crawl, state1, state2):
	global cursor
	checkNDEntryStatement = ('''SELECT * FROM nearduplicates WHERE nearduplicates.appname = '{0}'
								AND nearduplicates.crawl = '{1}'
								AND nearduplicates.state1 = '{2}' 
								AND nearduplicates.state2 = '{3}'
								''').format(appName, crawl, state1, state2)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(checkNDEntryStatement)
		ndEntries = cursor.fetchall()
		return ndEntries
	except Exception as e:
		print("ERROR CHECKING FOR APP ENTRY : " +appName + '_' +  crawl)	
		print(e)	
		return None					


def fetchRandomNearDuplicates(number, condition = ""):
	global cursor
	randNDStatement = ('''SELECT * FROM nearduplicates
						WHERE {0}
						DOM_RTED >=0 AND
						DOM_Levenshtein >=0 AND
						DOM_contentHash >=0 AND
						DOM_SIMHASH >=0 AND
						VISUAL_BlockHash >=0 AND 
						VISUAL_PHash >=0 AND
						VISUAL_Hyst >=0 AND 
						VISUAL_PDiff >=0 AND
						VISUAL_SIFT >=0 AND
						VISUAL_SSIM >=0 
						ORDER BY RANDOM() LIMIT {1} 
								''').format(condition , number)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(randNDStatement)
		ndRandEntries = cursor.fetchall()
		return ndRandEntries
	except Exception as e:
		print("ERROR FETCHING RANDOM NEARDUPLICATES : " + condition)	
		print(e)	
		return None					

def fetchAllNearDuplicates(condition=""):
	global cursor
	fetchNDStatement = ('''SELECT * FROM nearduplicates {0}''').format(condition)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(fetchNDStatement)
		allNDEntries = cursor.fetchall()
		return allNDEntries
	except Exception as e:
		print("ERROR ALL NEARDUPLICATES : " + condition)	
		print(e)	
		return None	

def fetchAllStates(condition=""):
	global cursor
	fetchStateStatement = ('''SELECT * FROM states {0}''').format(condition)
	#print(checkCrawlEntryStatement)
	try: 
		cursor.execute(fetchStateStatement)
		allNDEntries = cursor.fetchall()
		return allNDEntries
	except Exception as e:
		print("ERROR ALL States : " + condition)	
		print(e)	
		return None	

def mergeDatabase(dbFile):
	global conn
	global cursor

	try:
		attachStatement = "ATTACH '{0}' as toMerge".format(dbFile)
		beginStatement = "BEGIN"
		deleteAppsStatement = "DELETE FROM main.apps WHERE name IN (SELECT name FROM toMerge.apps)"
		deleteNDStatement = "DELETE FROM main.nearduplicates WHERE appname IN (SELECT appname FROM toMerge.nearduplicates)"
		addAppsStatement = "INSERT INTO main.apps SELECT * FROM toMerge.apps"
		addNDStatement = "INSERT INTO main.nearduplicates SELECT * FROM toMerge.nearduplicates;"
		detachStatement = "DETACH toMerge"

		# script = '''ATTACH '{0}' as toMerge;
		# 			BEGIN;
		# 			DELETE FROM main.apps WHERE name IN (SELECT name FROM toMerge.apps);
		# 			DELETE FROM main.nearduplicates WHERE appname IN (SELECT appname FROM toMerge.nearduplicates);
		# 			INSERT INTO main.apps SELECT * FROM toMerge.apps;
		# 			INSERT INTO main.nearduplicates SELECT * FROM toMerge.nearduplicates;
		# 			DETACH toMerge;
		# 			'''.format(dbFile)


		#cursor.executescript(script)
		cursor.execute(attachStatement)
		cursor.execute(beginStatement)
		cursor.execute(deleteAppsStatement)
		deletedApps = cursor.rowcount
		cursor.execute(deleteNDStatement)
		deletedNDs = cursor.rowcount
		cursor.execute(addAppsStatement)	
		insertedApps = cursor.rowcount
		cursor.execute(addNDStatement)
		insertedNDs = cursor.rowcount
		conn.commit()
		cursor.execute(detachStatement)
		print("In this merge")
		print("Deleted {0} apps and {1} Near Duplciates.".format(deletedApps, deletedNDs))
		print("Inserted {0} apps and {1} Near Duplicates.".format(insertedApps, insertedNDs))
		return {'status': True, 'deletedApps': deletedApps, 'deletedNDs' : deletedNDs, 'insertedApps' : insertedApps, 'insertedNDs': insertedNDs}

	except Exception as e: 
		print("COULD NOT MERGE THE GIVEN DATABASE : " + dbFile)
		print(e)
		return {'status': False, 'deletedApps': -1, 'deletedNDs' : -1, 'insertedApps' : -1, 'insertedNDs': -1}
###########################################################################
## File Operations ############
###########################################################################

#### Get all files of a pattern in path #################################

def find(pattern, path):
    result = []
    for root, dirs, files in os.walk(path):
        for name in files:
            if fnmatch.fnmatch(name, pattern):
                result.append(os.path.join(root, name))
    return result

def findDir(pattern, path):
	result = []
	for root, dirs, files in os.walk(path):
		for name in dirs:
			if fnmatch.fnmatch(name, pattern):
				result.append(os.path.join(root, name))
	return result


def getStateNames(appName, crawl):
	result = []
	states = find('*.html', CRAWL_PATH+ appName + '/' + crawl + '/' + STATES)
	for state in states:
		result.append(os.path.splitext(os.path.basename(state))[0])
	return result

def splitPathIntoFolders(path):
	folders = []
	path, file = os.path.split(path)
	print(path)
	while 1:
		path, folder = os.path.split(path)
		# print(folder)
		if folder != "":
			folders.append(folder)
		else:
			if path != "":
				folders.append(path)
			break

	# folders.reverse()
	return folders


###########################################################################
## Miscellaneous ############
###########################################################################
def getNumberFromString(string):
	try:
		return int(''.join(filter(str.isdigit, string)))
	except:
		return -1
###########################################################################
## Image Manipulations ############
###########################################################################

## Image Size ###############################
def getImageSize(image):
	im = Image.open(image)
	return im.size[0] * im.size[1]

## creating nearduplicate images ############
def createNearDuplicate(image1, image2, outputName, outputPath):
	imageName1 = 'index' if image1==0 else 'state'+image1
	imageName2 = 'index' if image2==0 else 'state'+image2
	im1 = Image.open(SCREENSHOTS_PATH+imageName1)
	im2 = Image.open(SCREENSHOTS_PATH+imageName2)
	im1width, im1height = im1.size
	im2width, im2height = im2.size
	total_width = im1width + im2width
	max_height = max(im1height, im2height)
	x_offset = 0
	new_im = Image.new('RGB', (total_width, max_height))
	new_im.paste(im1, (x_offset,0))
	x_offset += im1width
	new_im.paste(im2, (x_offset,0))
	new_im = new_im.resize((int(total_width*2/3), int(max_height*2/3)), Image.ANTIALIAS)
	new_im.save(outputPath + outputName, quality=40)




###########################################################################
## Parse Logs ############
###########################################################################
def getAllFinishedComputations(finishedLog):
	global LOGS_PATH
	returnArray = []
	f = open(finishedLog, 'r')
	lines = f.readlines()
	f.close()

	for i, line in enumerate(lines):   
		appName = (line.split(':')[2]).strip().split(' ')[0]
		crawl = line.split(':')[3].strip()
		#print('appname : ' + appName + ' crawl : ' + crawl)
		returnArray.append({'appName': appName, 'crawl':crawl})

	return returnArray




###########################################################################
## Parse comparision CSV ############
###########################################################################
def nearDuplicatePairsFromCSV(csvFile, threshold, comparision, max_value=-1):
	threshold = float(threshold)
	returnArray = []
	names = []
	with open(csvFile) as csv_file:
		csv_reader = csv.reader(csv_file, delimiter=',')
		line_count = 0 

		for row in csv_reader:	
			column_count = 0 
			if line_count == 0: 
				for column in row:
					names.append(os.path.splitext(column)[0])
				line_count +=1	
				continue
			
			#print(row)
			for column in row:
				if(column_count <= line_count-1):
					column_count +=1 
					continue

				if(max_value != -1):
					max_value_now = max(
						float(max_value[names[line_count-1]]), 
						float(max_value[names[column_count]])
						)
					#print(max_value_now)
					threshold = float(max_value_now)*threshold

				if ((comparision=='lt') and (float(column) <= threshold)) or ((comparision == 'gt') and (float(column) >= threshold)):
					#print(column + ':' + str(threshold))
					returnArray.append({'state1':names[line_count-1], 'state2':names[column_count], 'distance':column})


				#print(str(line_count-1) + ',' + str(column_count))
				column_count += 1

			line_count += 1

		print('Processed {0} lines.'.format(line_count))
		return returnArray

def getAllPairsFromCSV(csvFile, namesFromJson = None):
	returnArray = []
	names = []
	with open(csvFile) as csv_file:
		csv_reader = csv.reader(csv_file, delimiter=',')
		line_count = 0 
		skipped = 0

		for row in csv_reader:	
			column_count = 0 
			if line_count == 0: 
				for column in row:
					names.append(os.path.splitext(column)[0])
				line_count +=1	
				continue
			
			if not ((namesFromJson) is None):
				if (names[line_count-1] not in namesFromJson):
					# print("skipping row {0}".format(names[line_count -1]))
					line_count +=1	
					skipped +=1
					continue

			for column in row:
				if(column_count <= line_count-1):
					column_count +=1 
					continue

				if not ((namesFromJson)is None):
					if (names[column_count] not in namesFromJson):
						column_count +=1 
						continue

				returnArray.append({'state1':names[line_count-1], 'state2':names[column_count], 'distance':column})
				column_count += 1

			line_count += 1

		print('Processed {0} lines.'.format(line_count-skipped))
		return returnArray

def getMaxRawPdiff(appName, crawl):
	returnMap={}
	path = CRAWL_PATH+ '/' + appName + '/' + crawl + '/' + 'screenshots' 
	allfiles = find('*.png', path)
	for file in allfiles:
		size = getImageSize(file)
		returnMap[os.path.splitext(os.path.basename(file))[0]] = size
		#print(os.path.splitext(os.path.basename(file))[0] + ':' + str(size))
	return returnMap
	

###########################################################################
## Tests ############
##########################################################################
testAppName = 'fide.com'
testCrawl = 'crawl0'

def testLogparse():
	allFinishedComputations = getAllFinishedComputations()
	print(allFinishedComputations)

def testCSVparse():
	for algo in ALGOS:
		print(algo.value)
		csvFile = CRAWL_PATH + testAppName + '/' + testCrawl + '/' + COMP_OUTPUT + '/'  + testCrawl + '-' + algo.value[0] + '-raw.csv'
		pairs =	nearDuplicatePairsFromCSV(csvFile, algo.value[1], algo.value[2])
		print(pairs)

def testCSVparseWithMaxRaw():
	csvFile = CRAWL_PATH + testAppName + '/' + testCrawl + '/' + COMP_OUTPUT + '/'  + testCrawl + '-' + 'VISUAL-PDiff' + '-raw.csv'
	maxRaw = getMaxRawPdiff(testAppName, testCrawl)
	pairs =	nearDuplicatePairsFromCSV(csvFile, '0.2', maxRaw)
	print(pairs)

def testFileFinder():
	path = CRAWL_PATH+ '/' + testAppName + '/' + testCrawl + '/' + 'screenshots' 
	allfiles = find('*.png', path)
	print(allfiles)

def testImageSizeGetter():
	path = CRAWL_PATH+ '/' + testAppName + '/' + testCrawl + '/' + 'screenshots' 
	allfiles = find('*.png', path)
	for file in allfiles:
		size = getImageSize(file)
		print(file + ':' + str(size))

def testMaxRawFinder():
	maxRaw = getMaxRawPdiff(testAppName, testCrawl)
	print(maxRaw)

def testDBAppEntry():
	connectToDB(DB_PATH + 'test.db')
	resetDatabase()
	if addCrawl('testapp', 'testcrawl', 10, 100, 5):
		print("SUCCESSFULLY added entry")
	if checkCrawlEntryExists('testapp', 'testcrawl'):
		print("ADDED ENTRY EXISTS !! ")
	closeDBConnection()

def testDBNearDuplicateEntry():
	connectToDB(DB_PATH + 'test.db')
	resetDatabase()
	if addCrawl('testapp', 'testcrawl', 10, 100, 5):
		print("SUCCESSFULLY added entry")
	if checkCrawlEntryExists('testapp', 'testcrawl'):
		print("ADDED ENTRY EXISTS !! ")
	if addNearDuplicate('testapp', 'testcrawl', 'teststate1', 'teststate1'):
		print("SUCCESSFULLY ADDED NEAR DUPLICATE")
	if addNearDuplicate('garbage', 'testcrawl1', 'teststate1', 'teststate1'):
		print("SHOULD NOT HAVE ADDED NEAR DUPLICATE")
	else:
		print('SUCCESSFULLY CHECKED FOREIGN KEY CONSTRAINT')
	
	closeDBConnection()


def testDBUpdate():
	connectToDB(DB_PATH + 'test.db')
	resetDatabase()
	
	if addCrawl('testapp', 'testcrawl', 10, 100, 5):
		print("SUCCESSFULLY added app entry")
	if checkCrawlEntryExists('testapp', 'testcrawl'):
		print("ADDED ENTRY EXISTS !! ")
	if addNearDuplicate('testapp', 'testcrawl', 'teststate1', 'teststate1'):
		print("SUCCESSFULLY ADDED NEAR DUPLICATE")
	if addNearDuplicate('testapp', 'testcrawl', 'teststate1', 'teststate1'):
		print("SHOULD NOT BE ADDED NEAR DUPLICATE")		
	for algo in ALGOS:
	 	if updateNearDuplicate('testapp', 'testcrawl', 'teststate1', 'teststate1', str(algo).split('.')[1], 0.02):
	 		print('ENTRY UPDATE SUCCESFUL')
	if updateNearDuplicate('testapp', 'testcrawl', 'teststate1', 'teststate1', 'test_saf2', 0.02):
		print('ENTRY UPDATE WHEN NOT algo not EXUSTS TESTED')

	print(fetchNearDuplicates('testapp', 'testcrawl','teststate1', 'teststate1'))
	closeDBConnection()


def testRandomFetch():
	connectToDB(DB_PATH + 'test.db')
	resetDatabase();
	for i in range(1000):
		for algo in ALGOS:
			updateNearDuplicate('testapp'+str(i), 'testcrawl' + str(i), 'teststate1' + str(i), 'teststate1' + str(i), str(algo).split('.')[1], 0.02*i)
	randomNDs = fetchRandomNearDuplicates(100)
	print(randomNDs);
	closeDBConnection()



def testmultiUpdate():
	testDB =DB_PATH + 'test.db'
	#testDB = '/Test/combined.db'
	connectToDB(testDB)

	resetDatabase();
	
	for i in range(10):
		tuples = {}
		if addCrawl('testapp' + str(i), 'testcrawl'+str(i), 10, 100, 5):
			print("SUCCESSFULLY added app entry")
		if checkCrawlEntryExists('testapp' + str(i), 'testcrawl'+str(i)):
			print("ADDED ENTRY EXISTS !! ")
		#tuples[( 'state1'+ str(i), 'state2'+str(i))] = {}
		for algo in ALGOS:
			for j in range(10):
				state1 = 'state' + str(j)
				state2 = 'state' + str(j+2)
			
				if ('testapp' + str(i) , 'testcrawl' + str(i), state1, state2) in tuples:
				#	print("TUPLE already present updating my value to it")
					tuples[( ('testapp' + str(i) , 'testcrawl' + str(i), state1, state2))][str(algo).split('.')[1]] = 0.02*i*j
				else:
				#	print("TUPLE not present creating it ")
					tuples[( ('testapp' + str(i) , 'testcrawl' + str(i), state1, state2))] = {}
					tuples[( ('testapp' + str(i) , 'testcrawl' + str(i), state1, state2))][str(algo).split('.')[1]] = 0.02*i*j
		print("Updating my tuples for : testapp " + str(i))
		#print(tuples)

		added, failed = updateNearDuplicateMulti(tuples)
		print(str(added)  + " tuples added and " + str(failed) + " tuples failed" )


	randomNDs = fetchRandomNearDuplicates(100)
	print(randomNDs);

	randomNDsWithCondition = fetchRandomNearDuplicates(100, "appName in (select name from apps where numAddedStates>=10) AND ")
	print(randomNDsWithCondition)

	closeDBConnection()

def testRandomFetchWithCondition():
	testDB = '/Test/combined.db'
	connectToDB(testDB)
	randomNDsWithCondition = fetchRandomNearDuplicates(100, "appname in (select name from apps where numAddedStates>=10) AND ")
	for randND in randomNDsWithCondition:
		appRecords = getCrawlRecord(randND[0], randND[1])
		for appRecord in appRecords:
			if(int(appRecord[2]) <10):
				print("ERROR!! This record should not have been returned by the DB : " + str(randND) + " because the app entry is : " + str(appRecord))
	#print(randomNDsWithCondition)
	closeDBConnection()

def testStateNamesFromJson():
	crawl = os.path.join(os.path.abspath("../src/main/resources/GoldStandards"), 'pagekit', 'crawl-pagekit-60min_withForms')
	resultJson = os.path.join(crawl, RESULT_JSON)
	names = getStateNamesFromJson(resultJson)
	print(str(len(names)))

def	testAllPairsFromCsv():
	crawl = os.path.join(os.path.abspath("../src/main/resources/GoldStandards"), 'pagekit', 'crawl-pagekit-60min_withForms')
	resultJson = os.path.join(crawl, RESULT_JSON)
	names = getStateNamesFromJson(resultJson)

	csv = os.path.join(crawl, COMP_OUTPUT, "crawl-pagekit-60min_withForms-VISUAL-SIFT-raw.csv")
	noFilter = getAllPairsFromCSV(csv)
	withFilter = getAllPairsFromCSV(csv, names)
	# print(str(len(names)))
	print(str(len(noFilter)))
	print(str(len(withFilter)))

#testLogparse()
#testCSVparse()	
#testFileFinder()
#testImageSizeGetter()
#testMaxRawFinder()
#testCSVparseWithMaxRaw()
#testDBAppEntry()
#testDBNearDuplicateEntry()
#testDBUpdate()
#testRandomFetch()
#testmultiUpdate()
#testRandomFetchWithCondition()

###########################################################################
## Main Code ############
###########################################################################
def entriesFromLogs():
	allFinishedComputations = getAllFinishedComputations(LOGS_PATH + FINISHED_LOG)

	print(allFinishedComputations)


	totalStates = 0
	totalCrawledStates = 0
	totalNearDuplicates = 0
	doneProcessing = []
	processedNow = []
	for finished in allFinishedComputations:
		tuples = {}
		appName = finished['appName']
		crawl = finished['crawl']
		if checkCrawlEntryExists(appName, crawl):
			print('ENTRY ALREADY EXISTS. IGNORING : ' + appName + ' : ' + crawl)
			if not (appName + '_' + crawl ) in doneProcessing:
				doneProcessing.append(appName + "_" +crawl)
			continue
		else: 
			print('NO PRIOR ENTRY!! PROCESSING : ' + appName + ' : ' + crawl)
			stateNames = getStateNames(appName, crawl)
			numAddedStates = len(stateNames)
			totalStates += numAddedStates
			if(numAddedStates == 0):
				print('IGNORING {0} BECAUSE NO STATES'.format(appName))
				continue

			print(stateNames)
			statenumbers= [getNumberFromString(i) for i in stateNames]
			print(statenumbers)
			maxStateNumber = max([getNumberFromString(i) for i in stateNames])
			totalCrawledStates+=maxStateNumber
			print(maxStateNumber)
			if addCrawl(appName, crawl, len(stateNames), maxStateNumber, 5):
				print("SUCCESSFULLY added entry")

			for algo in ALGOS:
				print(algo.value)
				csvFile = CRAWL_PATH + appName + '/' + crawl + '/' + COMP_OUTPUT + '/'  + crawl + '-' + algo.value[0] + '-raw.csv'
				if not (os.path.exists(csvFile)):
					continue
				#pairs =	nearDuplicatePairsFromCSV(csvFile, algo.value[1], algo.value[2])
				pairs = getAllPairsFromCSV(csvFile)
				for pair in pairs:
					state1 = pair['state1']
					state2 = pair['state2']
					if (appName, crawl,state1, state2) in tuples:
				#		print("TUPLE already present updating my value to it")
						tuples[(appName, crawl, state1, state2)][str(algo).split('.')[1]] = pair['distance']
					else:
				#		print("TUPLE not present creating it ")
						tuples[(appName, crawl, state1, state2)] = {}
						tuples[(appName, crawl, state1, state2)][str(algo).split('.')[1]] = pair['distance']

					# if updateNearDuplicate(appName, crawl, pair['state1'], pair['state2'], str(algo).split('.')[1], pair['distance']):
					# 	print('SUCCESSFULLY ADDED NEAR DUPLICATE : ' + str(pair) + ' : ' + appName +  ' : ' + crawl)
					# else :
					# 	print('COULD NOT ADD NEAR DUPLICATE : ' + str(pair) + ' : ' + appName +  ' : ' + crawl)
				#print(pairs)
				totalNearDuplicates += len(pairs)
			
			added, failed = updateNearDuplicateMulti(tuples)
			print(str(added)  + " tuples added and " + str(failed) + " tuples failed" )

			doneProcessing.append(appName + "_" +crawl)
			processedNow.append(appName + "_" + crawl)

	print(str(len(doneProcessing)) + ' CRAWLS PROCESSED INTO DB IN TOTAL')
	print(str(len(processedNow)) + ' CRAWLS PROCESSED NOW')
	print(totalStates)
	print(totalCrawledStates)
	print(totalNearDuplicates)

def getStateNamesFromJson(resultJson):
	names = []
	if not os.path.exists(resultJson):
		print("Result JSON not found at : {0}".format(resultJson))
		return None
	resultJsonData = importJson(resultJson)
	states = resultJsonData['states']
	for state in states:
		names.append(states[state]['name'])

	print(names)
	return names


def getStateNames(csvFile):
	names = []
	with open(csvFile) as csv_file:
		csv_reader = csv.reader(csv_file, delimiter=',')

		row = next(csv_reader)	
			
		for column in row:
			names.append(os.path.splitext(column)[0])
		
	return names

def entriesFromFS():
	baseCSVs = find('*DOM-RTED-raw.csv', CRAWL_PATH)
	# updatedCSVs, algo, db
	totalupdated = 0
	totalSynced = 0
	totalStates = 0
	totalCrawledStates = 0
	totalNearDuplicates = 0
	doneProcessing = []
	processedNow = []
	allAvailableApps = []
	try:
		# connectToDB(db)
		for csv in baseCSVs:
			allAvailable=True
			tuples = {}
		# csv = "/Test/gt10/parktherme.at/crawl0/comp_output/crawl0-DOM-RTED-raw-updated.csv"
		# if csv != None:
			# print(csv)
			folders = splitPathIntoFolders(csv)
			#print(folders)
			appName = folders[2]
			crawl = folders[1]
			if checkCrawlEntryExists(appName, crawl):
				print('ENTRY ALREADY EXISTS. IGNORING : ' + appName + ' : ' + crawl)
				if not (appName + '_' + crawl ) in doneProcessing:
					doneProcessing.append(appName + "_" +crawl)
				continue
			else: 
				print('NO PRIOR ENTRY!! PROCESSING : ' + appName + ' : ' + crawl)
				stateNames = getStateNames(csv)
				numAddedStates = len(stateNames)
				totalStates += numAddedStates
				if(numAddedStates == 0):
					print('IGNORING {0} BECAUSE NO STATES'.format(appName))
					continue

			print(stateNames)
			statenumbers= [getNumberFromString(i) for i in stateNames]
			print(statenumbers)
			maxStateNumber = max([getNumberFromString(i) for i in stateNames])
			totalCrawledStates+=maxStateNumber
			print(maxStateNumber)
			if addCrawl(appName, crawl, len(stateNames), maxStateNumber, 5):
				print("SUCCESSFULLY added entry")

			# print(os.path.join(appName ,crawl,  folders[0]))
			for algo in ALGOS:
				print(algo.value)
				csvFile = CRAWL_PATH + appName + '/' + crawl + '/' + COMP_OUTPUT + '/'  + crawl + '-' + algo.value[0] + '-raw.csv'
				if(algo == ALGOS.DOM_RTED):
					csvFile = CRAWL_PATH + appName + '/' + crawl + '/' + COMP_OUTPUT + '/'  + crawl + '-' + algo.value[0] + '-raw-updated.csv'
				if(algo == ALGOS.VISUAL_PDIFF):
					updatedcsvFile = CRAWL_PATH + appName + '/' + crawl + '/' + COMP_OUTPUT + '/'  + crawl + '-' + algo.value[0] + '-raw-updated.csv'
					normalizedcsvFile = CRAWL_PATH + appName + '/' + crawl + '/' + COMP_OUTPUT + '/'  + crawl + '-' + algo.value[0] + '-normalized.csv'
					if (os.path.exists(updatedcsvFile)):
						csvFile = updatedcsvFile
					elif (os.path.exists(normalizedcsvFile)):
						csvFile = normalizedcsvFile

				if not (os.path.exists(csvFile)):
					allAvailable = False
					continue
				
				print(csvFile)

				#pairs =	nearDuplicatePairsFromCSV(csvFile, algo.value[1], algo.value[2])
				pairs = getAllPairsFromCSV(csvFile)
				for pair in pairs:
					state1 = pair['state1']
					state2 = pair['state2']
					if (appName, crawl,state1, state2) in tuples:
				#		print("TUPLE already present updating my value to it")
						tuples[(appName, crawl, state1, state2)][str(algo).split('.')[1]] = pair['distance']
					else:
				#		print("TUPLE not present creating it ")
						tuples[(appName, crawl, state1, state2)] = {}
						tuples[(appName, crawl, state1, state2)][str(algo).split('.')[1]] = pair['distance']

					# if updateNearDuplicate(appName, crawl, pair['state1'], pair['state2'], str(algo).split('.')[1], pair['distance']):
					# 	print('SUCCESSFULLY ADDED NEAR DUPLICATE : ' + str(pair) + ' : ' + appName +  ' : ' + crawl)
					# else :
					# 	print('COULD NOT ADD NEAR DUPLICATE : ' + str(pair) + ' : ' + appName +  ' : ' + crawl)
				#print(pairs)
				totalNearDuplicates += len(pairs)
			added = 0
			failed = 0
			added, failed = updateNearDuplicateMulti(tuples)
			print(str(added)  + " tuples added and " + str(failed) + " tuples failed" )

			doneProcessing.append(appName + "_" +crawl)
			processedNow.append(appName + "_" + crawl)
			if allAvailable:
				allAvailableApps.append(appName + "_" +crawl)

			# pairs = getAllPairsFromCSV(csv)
			# updatedPairs, ignoredPairs, sameValuePairs, errorPairs = updateDBWithAlgoPairs(pairs, appName, crawl, algo)
			# totalupdated += updatedPairs
			# totalSynced += updatedPairs
			# totalSynced += sameValuePairs
			# print("Updated : {0}, Ignored not present in db: {1}, Ignored same Value : {2}, Errored : {3}  db records".format(updatedPairs, ignoredPairs, sameValuePairs, errorPairs))
	except Exception as e:
		print(e)
		print("Encountered exception while updating records")
	# finally:
		# closeDBConnection()

	print(str(len(baseCSVs)) + ' CRAWLS AVAILABLE')
	print(str(len(allAvailableApps)) + ' CRAWLS AVAILABLE')

	print(str(len(doneProcessing)) + ' CRAWLS PROCESSED INTO DB IN TOTAL')
	print(str(len(processedNow)) + ' CRAWLS PROCESSED NOW')

	# print("Updated total {0} db records from {1} csvs".format(totalupdated, len(updatedCSVs)))
	# print("Synced total {0} db records from {1} csvs".format(totalSynced, len(updatedCSVs)))

def addHumanResponsesToTuples(responseData, tuples, appName, crawlName):
	errored = 0
	succeeded = 0
	pairs = responseData['pairs']
	for pair in pairs:
		state1 = pair['state1']
		state2 = pair['state2']
		if (appName, crawlName,state1, state2) in tuples:
			Tuple = tuples[(appName, crawlName, state1, state2)]
			for algo in Tuple:
				pair[algo] = Tuple[algo]

			Tuple['HUMAN_CLASSIFICATION'] = pair['response']
			Tuple['TAGS'] = ''' '{0}' '''.format(' ,'.join(pair['tags']))
			tuples[(appName, crawlName, state1, state2)] = Tuple
			succeeded+=1
		else:
			print("No distance tuple for classification pair : ")
			print(pair)
			errored+=1

	# responseData['pairs'] = pairs
	return tuples, responseData, succeeded, errored

def updateStateCharacteristics(appName, crawl, domSizes, nodeSizes, pixelSizes, statesJson=None, insertIfNotPresent=True):
	updatedPairs = 0
	ignoredPairs = 0
	sameValuePairs = 0
	errorPairs =0
	#randomNDs = fetchRandomNearDuplicates(NUMBER*2)
	for state in domSizes:
		try:
			domSize = domSizes[state][0]
			strippedDomSize = domSizes[state][1]
			domStructureSize = domSizes[state][2]
			domContentSize = domSizes[state][3]
			if(nodeSizes != None):
				nodeSize = nodeSizes[state]
			else:
				nodeSize = -1

			if(pixelSizes!=None):
				pixelSize = pixelSizes[state]
			else:
				pixelSize = -1

			stateTuple = {'domSize':domSize, 'strippedDomSize':strippedDomSize, 'domStructureSize':domStructureSize, 'domContentSize':domContentSize, 'nodeSize':nodeSize, 'pixelSize':pixelSize}
			
			if statesJson !=None:
				stateTuple['url'] = "'" + statesJson[state]['url'] + "'" 

			Inserted, Updated, Ignored, SameValue, Error = updateState(appName, crawl, state, stateTuple, insertIfNotPresent=insertIfNotPresent)
			if Error:
				errorPairs+=1
			
			if Ignored:
				ignoredPairs +=1

			if SameValue:
				sameValuePairs +=1

			if Updated:
				updatedPairs +=1


		except Exception as e:
			errorPairs+=1
			print(e)
			print("Exception while updating State : " + state)
	return updatedPairs, ignoredPairs, sameValuePairs, errorPairs

def getStateCharacteristics(CRAWL_PATH, appName, crawlName, insertIfNotPresent=True):
	CRAWL_PATH = os.path.abspath(CRAWL_PATH)
	resultJson = os.path.join(CRAWL_PATH, RESULT_JSON)
	domSizeJson = os.path.join(CRAWL_PATH, COMP_OUTPUT,  DOMSIZES_JSON)
	nodeSizeJson = os.path.join(CRAWL_PATH, COMP_OUTPUT,  NODESIZES_JSON)
	pixelSizeJson = os.path.join(CRAWL_PATH, COMP_OUTPUT, PIXELSIZES_JSON)
	updated=0
	ignored=0
	sameValue=0
	error=0 
	status = False
	result = {	'updatedStates' : updated,
				'ignoreStates': ignored,
				'sameValueStates':sameValue,
				'erroredStates': error,
				'done':status}

	resultStates = None
	if os.path.exists(resultJson):
		resultJsonData = importJson(resultJson)
		resultStates = resultJsonData['states']


	if os.path.exists(domSizeJson) and os.path.exists(nodeSizeJson) and os.path.exists(pixelSizeJson):
		domSizes = importJson(domSizeJson)
		nodeSizes = importJson(nodeSizeJson)
		pixelSizes = importJson(pixelSizeJson)
		updated, ignored, sameValue, error = updateStateCharacteristics(appName, crawlName, domSizes = domSizes, nodeSizes = nodeSizes, pixelSizes = pixelSizes, statesJson = resultStates, insertIfNotPresent = insertIfNotPresent)
		status = True
	
	result = {	'updatedStates' : updated,
				'ignoredStates': ignored,
				'sameValueStates':sameValue,
				'erroredStates': error,
				'done':status}
	print(result)
	return result

def updateCrawlEntry(appName, crawl, csv, crawlTime):
	stateNames = getStateNames(csv)
	numAddedStates = len(stateNames)
	if(numAddedStates == 0):
		print('IGNORING {0} BECAUSE NO STATES'.format(appName))
		return result

	# print(stateNames)
	statenumbers= [getNumberFromString(i) for i in stateNames]
	# print(statenumbers)
	maxStateNumber = max([getNumberFromString(i) for i in stateNames])
	# print(maxStateNumber)

	if checkCrawlEntryExists(appName, crawl):
		if updateCrawl(appName, crawl, numAddedStates, maxStateNumber, crawlTime):
			return True

	if addCrawl(appName, crawl, numAddedStates, maxStateNumber, crawlTime):
		print("SUCCESSFULLY added entry")
		return True

	return False

def getCrawlDistancePairs(CRAWL_PATH, appName, crawlName, humanResponsesAvailable=True, updateExisting = True, crawlTime = 5):
	
	allAvailable=True
	classification_updated = False
	tuplesUpdatedWithResponses = 0
	tuplesFailedToUpdateWithResponses= 0
	overwrittenDB = False
	tuplesAddedToDB = 0
	tuplesFailedToAdd = 0 
	
	tuples = {}
	CRAWL_PATH = os.path.abspath(CRAWL_PATH)
	if checkCrawlEntryExists(appName, crawlName):
		print('ENTRY ALREADY EXISTS : ' + appName + ' : ' + crawlName)
		if updateExisting:
			overwrittenDB = True
		else:
			result = {'overwrittenDB':overwrittenDB,
			 'allAvailable':allAvailable, 
			 'classification_updated':classification_updated,
			 'tuplesAddedToDB': tuplesAddedToDB, 
			 'tuplesFailedToAdd':tuplesFailedToAdd, 
			 'tuplesUpdatedWithResponses': tuplesUpdatedWithResponses, 
			 'tuplesFailedToUpdateWithResponses':tuplesFailedToUpdateWithResponses}
			return result
	
	print('NO PRIOR ENTRY!! PROCESSING : ' + appName + ' : ' + crawlName)
	
	crawlEntryUpdated = False

	namesFromJson = None

	resultJson = os.path.join(CRAWL_PATH, RESULT_JSON)
	if (os.path.exists(resultJson)):
		namesFromJson = getStateNamesFromJson(resultJson)
	else:
		print("Warning!! Result json not found at {0}".format(resultJson))


	for algo in ALGOS:
		print(algo.value)
		csvFile = os.path.join(CRAWL_PATH, COMP_OUTPUT, crawlName + '-' + algo.value[0] + '-raw.csv')
		
		if not (os.path.exists(csvFile)):
			allAvailable = False
			print("ERROR!! Could not find : " + csvFile)
			continue

		if not crawlEntryUpdated:
			if updateCrawlEntry(appName, crawlName, csvFile, crawlTime):
				crawlEntryUpdated = True



		pairs = getAllPairsFromCSV(csvFile, namesFromJson)
		for pair in pairs:
			state1 = pair['state1']
			state2 = pair['state2']
			distance = pair['distance']
			try:
				float_value = getNumberFromString(distance)
				if float_value == -1:
					distance = -1
					print("Distance not a float value")
					print(pair)
			except Exception as ex:
				print(ex)
				print("Distance not a float value")
				print(pair)
				distance = -1

			if (appName, crawlName,state1, state2) in tuples:
				tuples[(appName, crawlName, state1, state2)][str(algo).split('.')[1]] =distance
			else:
				tuples[(appName, crawlName, state1, state2)] = {}
				tuples[(appName, crawlName, state1, state2)][str(algo).split('.')[1]] =distance


	####
	if humanResponsesAvailable:
		responses = None
		gsJson = os.path.join(CRAWL_PATH, 'gs', GS_JSON_NAME)
		verifClassificationJson = os.path.join(CRAWL_PATH, 'comp_output', VERIFIED_CLASSIFICATION_JSON_NAME)
		if os.path.exists(gsJson):
			responses = importJson(gsJson)
		elif os.path.exists(verifClassificationJson):
			print("GS JSON not found at : {0}".format(gsJson))
			print("Using verified human classification")
			responses = importJson(verifClassificationJson)
		else:
			print("Verified Classification also not found. {0}".format(verifClassificationJson))
			print("Setting human responses to -1 in the db")
		if responses!= None:
			tuples, responseJsonWithDistances, tuplesUpdatedWithResponses, tuplesFailedToUpdateWithResponses = addHumanResponsesToTuples(responses, tuples, appName, crawlName)
			# print(tuples)
			# print(responseJsonWithDistances)
			if(tuplesFailedToUpdateWithResponses !=0 ):
				print("ERROR!! SOME RESPONSES COULD NOT BE ADDED TO DISTANCE TUPLES!!")
			updatedClassificationJson = os.path.join(CRAWL_PATH, 'comp_output', DISTANCES_RESPONSES_JSON)
			try:
				with open(updatedClassificationJson, 'w') as write_file:
					json.dump(responseJsonWithDistances, write_file)
					classification_updated = True
			except Exception as ex:
				print(ex)
				print("Could not output the updated classification json")
	tuplesAddedToDB, tuplesFailedToAdd = updateNearDuplicateMulti(tuples)
	print(str(tuplesAddedToDB)  + " tuples added and " + str(tuplesFailedToAdd) + " tuples failed" )
	
	result = {'overwrittenDB':overwrittenDB,
			 'allAvailable':allAvailable, 
			 'classification_updated':classification_updated,
			 'tuplesAddedToDB': tuplesAddedToDB, 
			 'tuplesFailedToAdd':tuplesFailedToAdd, 
			 'tuplesUpdatedWithResponses': tuplesUpdatedWithResponses, 
			 'tuplesFailedToUpdateWithResponses':tuplesFailedToUpdateWithResponses}

	return result


def createGSDB():
	GS_PATH = os.path.abspath("../src/main/resources/GoldStandards")
	db = os.path.join(GS_PATH, GS_DB_NAME)

	if(os.path.exists(db)) :
		connectToDB(db)
	else:
		print('CREATING THE GoldStandard DATABASE FOR THE FIRST TIME HERE : ' + db )
		connectToDB(db)
		createTables()

	# subjects = ["petclinic", "addressbook", "claroline"]
	subjects = ['pagekit']
	allAvailableCrawls = []
	for appName in subjects:
		crawlName = 'crawl-' + appName + '-60min'
		CRAWL_PATH = os.path.join(os.path.abspath(GS_PATH), appName, crawlName)
		if getStateCharacteristics(CRAWL_PATH, appName, crawlName):
			print("States added to DB")
		result = getCrawlDistancePairs(CRAWL_PATH, appName, crawlName, humanResponsesAvailable=True)
		print(result)
		if result['allAvailable']:
			allAvailableCrawls.append(crawlName)

	closeDBConnection()
	print("All Available Crawls : ")
	print(allAvailableCrawls)

if __name__ == '__main__':

	# testStateNamesFromJson()
	# testAllPairsFromCsv()
	
	createGSDB()
	# if len(sys.argv) == 3:
	# 	CRAWL_PATH = sys.argv[1]
	# 	LOGS_PATH = sys.argv[2]
	# 	DB_PATH = CRAWL_PATH

	# db = DB_PATH + DB_NAME

	# if(os.path.exists(db)) :
	# 	connectToDB(db)
	# else:
	# 	print('CREATING THE DATABASE FOR THE FIRST TIME HERE : ' + db )
	# 	connectToDB(db)
	# 	createTables()

	# entriesFromFS()
	
	# closeDBConnection()



