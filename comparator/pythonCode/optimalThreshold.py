from hyperopt import hp, tpe, fmin
import numpy as np
from sklearn import metrics
from RQ1 import getF1_Classifier
from pythonDBCreator import fetchAllNearDuplicates, connectToDB, closeDBConnection, ALGOS
from threshold_data import STATISTICS
from runCrawljaxBatch import normalize
from analyzeCrawl import writeCSV
# from globalNames import APPS
from datetime import datetime


allEntries = None
algoStr = None
distances = None
y_actual = []


def getDistances(algoStr):
	global allEntries
	global distances
	if allEntries == None:
		print("Entries not initialized yet")
		return 

	distances = []

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			if algoStr != str(algo).split('.')[1].upper():
				# print("skipping : ")
				# print(algo)
				index = index+1
				continue
			value = float(entry[index])
			distances.append(value)
	print("distances created for " + algoStr)
	print(len(distances))

def getMax(algoStr):
	if allEntries == None:
		data = normalize(STATISTICS['gt10_db_data']['all_all'])
		return data[algoStr][4]

	maxVal = -1
	for entry in allEntries:
		index=4
		for algo in ALGOS:
			if algoStr != str(algo).split('.')[1].upper():
				# print("skipping : ")
				# print(algo)
				index = index+1
				continue
			value = float(entry[index])
			if value>maxVal:
				maxVal = value

	return maxVal

def get_y_actual_SAF():
	global y_actual
	y_actual = []
	if allEntries == None:
		print("No entries initialized yet. Quitting!")
		sys.exit(0)

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			index=index+1

		if entry[index] == 0 or entry[index] ==1:
			y_actual.append(0)
		else:
			y_actual.append(1)

def get_y_actual_Classification():
	global y_actual
	y_actual = []
	if allEntries == None:
		print("No entries initialized yet. Quitting!")
		sys.exit(0)

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			index=index+1

		y_actual.append(entry[index])

def getF1_SAF_new(t):
	global algoStr
	global distances
	global y_actual
	algo = None
	for Algo in ALGOS:
		if algoStr == str(Algo).split('.')[1].upper():
			algo = Algo

	if Algo == None:
		print("No Algo found for the string specified. Quitting!!")
		sys.exit(-1)
	y_pred = []

	for value in distances:
		pred = 0
		if algo.value[2] == "lt":
			if value <= t:
				pred = 0
			else:
				pred = 1
		else:
			if value >= t:
				pred = 0
			else:
				pred = 1

		y_pred.append(pred)

	cm = metrics.confusion_matrix(y_actual, y_pred)
	# print(cm)
	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average=None)
	
	return f1[1]


def getF1_Classifier_new(tc, tn):
	global algoStr
	global distances
	global y_actual
	algo = None
	for Algo in ALGOS:
		if algoStr == str(Algo).split('.')[1].upper():
			algo = Algo

	if Algo == None:
		print("No Algo found for the string specified. Quitting!!")
		sys.exit(-1)

	y_pred = []
	for value in distances:
		pred = -1
		if algo.value[2] == "lt":
			if value <= tc:
				pred = 0
			if value > tc:
				if value <= tn:
					pred = 1
				else:
					pred = 2
		else:
			if value >= tc:
				pred = 0
			if value < tc:
				if value >= tn:
					pred = 1
				else:
					pred = 2

		y_pred.append(pred)

	cm = metrics.confusion_matrix(y_actual, y_pred)
	# print(cm)
	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average="macro")
	
	# print(f1)
	return f1


# def getF1_SAF(t):
# 	global algoStr
# 	global optimalThresholds
# 	global allEntries

# 	if allEntries == None:
# 		return -1

# 	y_pred = []
# 	y_actual = []

# 	for entry in allEntries:
# 		index=4
# 		for algo in ALGOS:
# 			if algoStr != str(algo).split('.')[1].upper():
# 				# print("skipping : ")
# 				# print(algo)
# 				index = index+1
# 				continue
# 			value = float(entry[index])
# 			pred= -1
# 			if algo.value[2] == "lt":
# 				if value <= t:
# 					pred = 0
# 				else:
# 					pred = 1
# 			else:
# 				if value >= t:
# 					pred = 0
# 				else:
# 					pred = 1

# 			y_pred.append(pred)
# 			index = index+1
		
# 		if entry[index] == 0 or entry[index] ==1:
# 			y_actual.append(0)
# 		else:
# 			y_actual.append(1)

# 	cm = metrics.confusion_matrix(y_actual, y_pred)
# 	# print(cm)
# 	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average=None)
	
# 	return f1[1]

# def getF1_Classifier(tc, tn):
# 	global algoStr
# 	global optimalThresholds
# 	global allEntries
# 	# print(algoStr)
# 	if allEntries == None:
# 		return -1

# 	y_pred = []
# 	y_actual = []

# 	for entry in allEntries:
# 		index=4
# 		for algo in ALGOS:
# 			if algoStr != str(algo).split('.')[1].upper():
# 				# print("skipping : ")
# 				# print(algo)
# 				index = index+1
# 				continue
# 			value = float(entry[index])
# 			pred = -1
# 			if algo.value[2] == "lt":
# 				if value <= tc:
# 					pred = 0
# 				if value > tc:
# 					if value <= tn:
# 						pred = 1
# 					else:
# 						pred = 2
# 			else:
# 				if value >= tc:
# 					pred = 0
# 				if value < tc:
# 					if value >= tn:
# 						pred = 1
# 					else:
# 						pred = 2

# 			y_pred.append(pred)
# 			index = index+1

# 		y_actual.append(entry[index])

# 	cm = metrics.confusion_matrix(y_actual, y_pred)
# 	# print(cm)
# 	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average="macro")
	
# 	# print(f1)
# 	return f1

def getLoss_Classification(space):
	tc = space['tc']
	tn = space['tn']
	return 1 - getF1_Classifier_new(tc, tn)

def getLoss_SAF(space):
	t = space['t']

	return 1 - getF1_SAF_new(t)

def getOptimalThresholds_SAF():
	global algoStr
	global allEntries
	optimalThresholds = []

	dbName="/comparator/src/main/resources/GoldStandards/SS.db"

	# dbName = "/Test/gt10/DS.db"
	excludeAlgos = []
	for appName in APPS:
		print(appName)
		connectToDB(dbName)
		allEntries = fetchAllNearDuplicates('where human_classification>=0 and appname="{0}"'.format(appName))
		closeDBConnection()
		print(len(allEntries))
		for algo in ALGOS:
			algoStr = str(algo).split('.')[1].upper()
			if(algoStr in excludeAlgos):
				continue
			print(algoStr)
			getDistances(algoStr)
			get_y_actual_SAF()
			space = {
				't': hp.uniform('t', 0, getMax(algoStr)),
			}
			try:
				best = fmin(fn = getLoss_SAF,
			            space = space, algo=tpe.suggest, 
			            max_evals = 1000)

				row = {'thresholdSet' : "optimal", 'appName':appName, 'algoName':algoStr,'thre':best['t']}
				optimalThresholds.append(row)
				print(best)
			except Exception as ex:
				print(ex)
				print("Error getting optimal threshold for {0}".format(algoStr))

	fieldNames = ['thresholdSet', 'appName', 'algoName', 'thre']
	writeCSV(fieldNames, optimalThresholds, "optimalThresholds_SAF.csv")

def getOptimalThresholds_Classification(iterations = 10000):
	global algoStr
	global allEntries
	optimalThresholds = []

	dbName = "/Test/gt10/DS.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()
	get_y_actual_Classification()

	for algo in ALGOS:
		algoStr = str(algo).split('.')[1].upper()
		print(algoStr)
		getDistances(algoStr)
		
		space = {
			'tc': hp.uniform('tc', 0, getMax(algoStr)),
			'tn': hp.uniform('tn', 0, getMax(algoStr))
		}

		best = fmin(fn = getLoss_Classification,
	            space = space, algo=tpe.suggest, 
	            max_evals = iterations)

		row = {'thresholdSet' : "optimal", 'algoName':algoStr, 'c-thre':best['tc'], 'n-thre':best['tn']}
		optimalThresholds.append(row)
	
		print(best)


	fieldNames = ['thresholdSet', 'algoName', 'c-thre', 'n-thre']
	writeCSV(fieldNames, optimalThresholds, "optimalThresholds_Classification" +str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".csv")

def getOptimalThreshold_SAF_Universal(algoString, iterations = 10000):
	global algoStr
	optimalThresholds = []
	global allEntries

	algoStr = algoString
	print(algoStr)
	dbName = "/Test/gt10/DS.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()
	getDistances(algoStr)
	get_y_actual_SAF()
	space = {
		't': hp.uniform('t', 0, getMax(algoStr)),
	}
	best = fmin(fn = getLoss_SAF,
		            space = space, algo=tpe.suggest, 
		            max_evals = iterations)

	row = {'thresholdSet' : "optimal", 'appName':"Universal", 'algoName':algoStr,'thre':best['t']}
	optimalThresholds.append(row)
	print(best)

	fieldNames = ['thresholdSet', 'appName', 'algoName', 'thre']
	writeCSV(fieldNames, optimalThresholds, "optimalThresholds_SAF_Universal" + algoStr + "_" + str(iterations) + str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".csv")
	return optimalThresholds


def getOptimalThreshold_SAF(algoString, iterations = 10000):
	global algoStr
	optimalThresholds = []
	global allEntries
	dbName = "/comparator/src/main/resources/GoldStandards/SS.db"
	algoStr = algoString
	print(algoStr)
	for appName in APPS:
		connectToDB(dbName)
		allEntries = fetchAllNearDuplicates('where human_classification>=0 and appname="{0}"'.format(appName))
		closeDBConnection()
		print(len(allEntries))

		getDistances(algoStr)
		get_y_actual_SAF()
		space = {
			't': hp.uniform('t', 0, getMax(algoStr)),
		}
		best = fmin(fn = getLoss_SAF,
			            space = space, algo=tpe.suggest, 
			            max_evals = iterations)

		row = {'thresholdSet' : "optimal", 'appName':appName, 'algoName':algoStr,'thre':best['t']}
		optimalThresholds.append(row)
		print(best)

	fieldNames = ['thresholdSet', 'appName', 'algoName', 'thre']
	writeCSV(fieldNames, optimalThresholds, "optimalThresholds_SAF_" + algoStr + "_" + str(iterations) + str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".csv")
	return optimalThresholds

def getOptimalThreshold_Classification(algoString): 
	global allEntries
	global algoStr
	algoStr = algoString
	optimalThresholds = []

	dbName = "/Test/gt10/DS.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()

	print(algoStr)
	getDistances(algoStr)
	get_y_actual_Classification()
	space = {
		'tc': hp.uniform('tc', 0, getMax(algoStr)),
		'tn': hp.uniform('tn', 0, getMax(algoStr))
	}

	best = fmin(fn = getLoss_Classification,
            space = space, algo=tpe.suggest, 
            max_evals = 10000)

	row = {'thresholdSet' : "optimal", 'algoName':algoStr, 'c-thre':best['tc'], 'n-thre':best['tn']}
	optimalThresholds.append(row)

	print(best)


	fieldNames = ['thresholdSet', 'algoName', 'c-thre', 'n-thre']
	writeCSV(fieldNames, optimalThresholds, "optimalThresholds_Classification_" + algoStr + "_" + str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".csv")



APPS = ['pagekit']

if __name__=="__main__":
	# try:
	# 	getOptimalThresholds_Classification(iterations = 10000)
	# except Exception as ex:
	# 	print(ex)
	# 	print("Could not obtain classification thresholds")
	# getOptimalThresholds_SAF()

	try:
		getOptimalThreshold_SAF("VISUAL_HYST", 5000)
	except Exception as ex:
		print(ex)
		print("Could not obtain app specific thresholds")

	
	# try:
	# 	getOptimalThreshold_SAF_Universal("VISUAL_HYST", iterations=50000)
	# except Exception as ex:
	# 	print("Could not obtain universal thresholds")


