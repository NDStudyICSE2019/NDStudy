from pythonDBCreator import connectToDB, closeDBConnection, SCREENSHOTS,  fetchRandomNearDuplicates, updateNearDuplicate, find, getAllPairsFromCSV, ALGOS, createTables, updateState, splitPathIntoFolders
from importResponses import importJson
import os
import csv
from updateAlgoValues import updateDBWithAlgoPairs

def num_after_point(x):
    s = str(x)
    if not '.' in s:
        return 0
    return len(s) - s.index('.') - 1


def getFieldNames(csvFile):
	names = []
	with open(csvFile) as csv_file:
		csv_reader = csv.reader(csv_file, delimiter=',')

		row = next(csv_reader)	
			
		for column in row:
			names.append(os.path.splitext(column)[0])
		
	return names

def writeNormalizedCSV(normalizedPairs, fieldnames, dst):
	csvRows = {}
	for pair in normalizedPairs:
		state1 = pair['state1']
		state2 = pair['state2']
		distance = pair['distance']
		if state1 not in csvRows:
			csvRows[state1] = {state1:0.0}
		if state2 not in csvRows:
			csvRows[state2] = {state2:0.0}

		csvRows[state1][state2] = distance
		csvRows[state2][state1] = distance 

	with open(dst, 'w') as csvfile:
		writer = csv.DictWriter(csvfile, fieldnames=fieldnames)

		writer.writeheader()
		for fieldname in fieldnames:
			writer.writerow(csvRows[os.path.splitext(fieldname)[0]])
			# writer.writerow({'first_name': 'Lovely', 'last_name': 'Spam'})
			# writer.writerow({'first_name': 'Wonderful', 'last_name': 'Spam'})

	print("Wrote Normalized csv : " + dst)

def normalizePDiff(pDiffPairs, pixelSizes):
	normalized =0
	needToRerun=True
	uncalculated = 0
	raw = True
	rawSet = False
	allZero = True

	if(pixelSizes == None):
		print("Cannot Normalize with None")
		return normalized

	for pDiffPair in pDiffPairs:
		distanceStr = pDiffPair['distance']
		numDec = num_after_point(distanceStr)
		distance = float(pDiffPair['distance'])
		
		
		distance = float(pDiffPair['distance'])
		
		if distance <0 :
			uncalculated +=1
		elif distance>=0 and distance <=1:
			if not rawSet:
				print("Setting raw to False")
				raw = False
			
			if distance>0:
				allZero = False
				# print(distance)
			if(numDec > 2):
				allZero = False
				needToRerun = False

			continue

		raw = True
		rawSet = True
		allZero = False
		needToRerun = False
		state1 = pDiffPair['state1']
		state2 = pDiffPair['state2']
		maxPixelSize = max(pixelSizes[state1], pixelSizes[state2])
		normDistance = distance/maxPixelSize
		pDiffPair['distance'] = normDistance
		normalized +=1 
		# print("{0} : {1} : {2}  ".format(distance, maxPixelSize, normDistance))

	#print("{0} : {1} : {2}  ".format(normalized, needToRerun, uncalculated))
	return normalized, uncalculated, raw, needToRerun, allZero, pDiffPairs

def testNormalizePDiff():
	pDiffRawCSVs = find('*PDiff-raw.csv', '/Test/gt10/')
	totalNormalized = 0
	totalUncalculated = 0
	totalupdated = 0
	totalSynced = 0
	appsToRerun=[]
	appsNormalized= []
	allZeroApps = []
	for pDiffRawCSV in pDiffRawCSVs:
	# pDiffRawCSV = "/Test/gt10/windowsvc.com/crawl0/comp_output/crawl0-VISUAL-PDiff-raw.csv"
	# pDiffRawCSV = "/Test/gt10/werner.com/crawl0/comp_output/crawl0-VISUAL-PDiff-raw.csv"
	
	# if pDiffRawCSV !=None:
		print(pDiffRawCSV)
		path, file = os.path.split(pDiffRawCSV)
		folders = splitPathIntoFolders(pDiffRawCSV)
		#print(folders)
		appName = folders[2]
		crawl = folders[1]
		pixelSizeJson = os.path.join(os.path.abspath(path), '', 'pixelSizes.json')
		pixelSizes = importJson(pixelSizeJson)
		pDiffPairs = getAllPairsFromCSV(pDiffRawCSV)
		normalized, uncalculated, raw, needToRerun, allZero, pDiffPairsNormalized = normalizePDiff(pDiffPairs, pixelSizes)
		totalNormalized += normalized
		totalUncalculated+= uncalculated
		if needToRerun:
			appsToRerun.append(appName)
		if raw:
			appsNormalized.append(appName)
			fieldnames = getFieldNames(pDiffRawCSV)
			dst = os.path.join(path, '', crawl+'-VISUAL-PDiff-normalized.csv')
			writeNormalizedCSV(pDiffPairsNormalized, fieldnames, dst)
			# try:
			# 	testdb = '/Test/DS.db'
			# 	connectToDB(testdb)
			# 	updatedPairs, ignoredPairs, sameValuePairs, errorPairs = updateDB(pDiffPairsNormalized, appName, crawl, str(ALGOS.VISUAL_PDIFF).split('.')[1])
			# 	totalupdated += updatedPairs
			# 	totalSynced += updatedPairs
			# 	totalSynced += sameValuePairs
			# 	print("Updated : {0}, Ignored not present in db: {1}, Ignored same Value : {2}, Errored : {3}  db records".format(updatedPairs, ignoredPairs, sameValuePairs, errorPairs))
			# except Exception as ex:
			# 	print(e)
			# 	print("Encountered exception while updating records")
			# finally:
			# 	closeDBConnection()
		if allZero:
			allZeroApps.append(appName)


	print("Noramlized total {0} PDiff distances from {1} apps".format(totalNormalized, len(pDiffRawCSVs)))
	print("Found total {0} uncalculated PDIff distances from {1} apps".format(totalUncalculated, len(pDiffRawCSVs)))

	print("appsToRerun : {0}" + str(len(appsToRerun)))
	print("rawApps : " + str(len(appsNormalized)))
	print("allZeroApps : " + str(len(allZeroApps)))
	print("intersection of appsToRerun and allZero. : " + str(len(list(set(appsToRerun) & set(allZeroApps)))))
	
	# print("Updated total {0} db records from {1} csvs".format(totalupdated, len(updatedCSVs)))
	# print("Synced total {0} db records from {1} csvs".format(totalSynced, len(updatedCSVs)))

	with open("Output.txt", "w") as text_file:
		for app in appsToRerun:
			print(app, file=text_file)

def testNormalizeHyst():
	pDiffRawCSVs = find('*Hyst-raw.csv', '/Test/gt10/')
	totalNormalized = 0
	totalUncalculated = 0
	totalupdated = 0
	totalSynced = 0
	appsToRerun=[]
	appsNormalized= []
	allZeroApps = []
	for pDiffRawCSV in pDiffRawCSVs:
	# pDiffRawCSV = "/Test/gt10/windowsvc.com/crawl0/comp_output/crawl0-VISUAL-PDiff-raw.csv"
	# if pDiffRawCSV !=None:
		print(pDiffRawCSV)
		path, file = os.path.split(pDiffRawCSV)
		folders = splitPathIntoFolders(pDiffRawCSV)
		#print(folders)
		appName = folders[2]
		crawl = folders[1]
		pixelSizeJson = os.path.join(os.path.abspath(path), '', 'pixelSizes.json')
		pixelSizes = importJson(pixelSizeJson)
		pDiffPairs = getAllPairsFromCSV(pDiffRawCSV)
		normalized, uncalculated, raw, needToRerun, allZero, pDiffPairsNormalized = normalizePDiff(pDiffPairs, pixelSizes)
		totalNormalized += normalized
		totalUncalculated+= uncalculated
		if needToRerun:
			appsToRerun.append(appName)
		if raw:
			appsNormalized.append(appName)
			fieldnames = getFieldNames(pDiffRawCSV)
			dst = os.path.join(path, '', crawl+'-VISUAL-Hyst-normalized.csv')
			writeNormalizedCSV(pDiffPairsNormalized, fieldnames, dst)
			# try:
			# 	testdb = '/Test/DS.db'
			# 	connectToDB(testdb)
			# 	updatedPairs, ignoredPairs, sameValuePairs, errorPairs = updateDB(pDiffPairsNormalized, appName, crawl, str(ALGOS.VISUAL_PDIFF).split('.')[1])
			# 	totalupdated += updatedPairs
			# 	totalSynced += updatedPairs
			# 	totalSynced += sameValuePairs
			# 	print("Updated : {0}, Ignored not present in db: {1}, Ignored same Value : {2}, Errored : {3}  db records".format(updatedPairs, ignoredPairs, sameValuePairs, errorPairs))
			# except Exception as ex:
			# 	print(e)
			# 	print("Encountered exception while updating records")
			# finally:
			# 	closeDBConnection()
		if allZero:
			allZeroApps.append(appName)


	print("Noramlized total {0} Hyst distances from {1} apps".format(totalNormalized, len(pDiffRawCSVs)))
	print("Found total {0} uncalculated Hyst distances from {1} apps".format(totalUncalculated, len(pDiffRawCSVs)))

	print("appsToRerun : {0}" + str(len(appsToRerun)))
	print("rawApps : " + str(len(appsNormalized)))
	print("allZeroApps : " + str(len(allZeroApps)))
	print("intersection of appsToRerun and allZero. : " + str(len(list(set(appsToRerun) & set(allZeroApps)))))
	
	# print("Updated total {0} db records from {1} csvs".format(totalupdated, len(updatedCSVs)))
	# print("Synced total {0} db records from {1} csvs".format(totalSynced, len(updatedCSVs)))

	with open("Output.txt", "w") as text_file:
		for app in appsToRerun:
			print(app, file=text_file)



if __name__=='__main__':
	testNormalizePDiff()
	#testNormalizeHyst()