from sklearn import metrics, dummy
from pythonDBCreator import fetchAllNearDuplicates, ALGOS, connectToDB, closeDBConnection
from runCrawljaxBatch import getThreshold
from analyzeCrawl import writeCSV
from globalNames import THRESHOLD_SETS, DB_SETS, RESULTS_FOLDER
from datetime import datetime
import os


def testDummyClassifier():
	dummyClassifier = dummy.DummyClassifier(strategy="stratified")
	
	X = [[0]] * 10
	y=[0,1,2,0,1,2,0,0,1,2]
	dummyClassifier.fit(X, y)

	dbName = "/Test/gt10/gt10_last500Responses.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()
	y_actual = []
	fieldNames = ['thresholdSet', 'algoName', 'c-thre', 'n-thre', 'precision', 'recall', 'f1']

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			index = index+1

		y_actual.append(entry[index])


	y_pred = dummyClassifier.predict(y_actual)

	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average="macro")
	# precision = metrics.precision(y_actual, y_pred[algoStr], average="macro")
	# recall = metrics.recall(y_actual, y_pred[algoStr], average="macro")
	# f1 = metrics.f1_score(y_actual, y_pred[algoStr], average="macro")
	row1 = {'thresholdSet' :None, 'algoName':"dummy", 'c-thre':None,'n-thre':None,'precision':precision, 'recall':recall, 'f1':f1}
	print(row1)
	
	dbName="/comparator/src/main/resources/GoldStandards/SS.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()
	y_actual = []

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			index = index+1

		y_actual.append(entry[index])


	y_pred = dummyClassifier.predict(y_actual)

	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average="macro")
	row2 = {'thresholdSet' :None, 'algoName':"dummy", 'c-thre':None,'n-thre':None,'precision':precision, 'recall':recall, 'f1':f1}
	print(row2)
	writeCSV(fieldNames, [row1, row2], "rq1_dummy.csv")

def getF1_Classifier(allEntries):
	y_pred = {}

	for algo in ALGOS:
		algoStr = str(algo).split('.')[1].upper()
		y_pred[algoStr] = []

	y_actual = []

	print(len(allEntries))

	threshold_sets = {}
	# threshold_sets["proportion_based"] = [getThreshold(THRESHOLD_SETS.FULLDB_QUART1, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]
	threshold_sets["statistical"] = [getThreshold(THRESHOLD_SETS.HUMANCLONE_QUART3, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.HUMANND_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]
	threshold_sets["optimal"] = [getThreshold(THRESHOLD_SETS.OPTIMAL_CLASSIFICATION_CLONE, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.OPTIMAL_CLASSIFICATION_ND, DB_SETS.GT10_DB_DATA, 'all')]

	algoScoreRows = []
	fieldNames = ['thresholdSet', 'algoName', 'c-thre', 'n-thre', 'precision', 'recall', 'f1']

	dummyClassifier = dummy.DummyClassifier(strategy="stratified")

	print(threshold_sets)
	for threshold_set_name in threshold_sets:
		threshold_set = threshold_sets[threshold_set_name]
		cloneThresholds = threshold_set[0]
		ndThresholds = threshold_set[1]
		# print(cloneThresholds)
		for entry in allEntries:
			index=4
			for algo in ALGOS:
				algoStr = str(algo).split('.')[1].upper()
				value = float(entry[index])
				pred = -1
				if algo.value[2] == "lt":
					if value <= cloneThresholds[algoStr]:
						pred = 0
					if value > cloneThresholds[algoStr]:
						if value <= ndThresholds[algoStr]:
							pred = 1
						else:
							pred = 2
				else:
					if value >= cloneThresholds[algoStr]:
						pred = 0
					if value < cloneThresholds[algoStr]:
						if value >= ndThresholds[algoStr]:
							pred = 1
						else:
							pred = 2

				y_pred[algoStr].append(pred)
				index = index+1

			y_actual.append(entry[index])


		for algo in ALGOS:
			algoStr = str(algo).split('.')[1].upper()
			cm = metrics.confusion_matrix(y_actual, y_pred[algoStr])
			# print(cm)
			precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred[algoStr], average="macro")
			# precision = metrics.precision(y_actual, y_pred[algoStr], average="macro")
			# recall = metrics.recall(y_actual, y_pred[algoStr], average="macro")
			# f1 = metrics.f1_score(y_actual, y_pred[algoStr], average="macro")
			row = {'thresholdSet' : threshold_set_name, 'algoName':algoStr, 'c-thre':cloneThresholds[algoStr],'n-thre':ndThresholds[algoStr],'precision':precision, 'recall':recall, 'f1':f1}
			algoScoreRows.append(row)
		X = [[0]] * len(y_actual)
		dummyClassifier.fit(X, y_actual)
		y_pred_dummy = dummyClassifier.predict(y_actual)
		precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred_dummy, average="macro")
		row2 = {'thresholdSet' :None, 'algoName':"dummy", 'c-thre':None,'n-thre':None,'precision':precision, 'recall':recall, 'f1':f1}
		algoScoreRows.append(row2)
	
	
	writeCSV(fieldNames, algoScoreRows, os.path.join(os.path.abspath(".."), RESULTS_FOLDER, "rq1_" + str(datetime.now().strftime("%Y%m%d-%H%M%S")) +".csv"))


def getF1_SAF(t, allEntries, algoStr):
	if allEntries == None:
		return -1

	y_pred = []
	y_actual = []

	for entry in allEntries:
		index=4
		for algo in ALGOS:
			if algoStr != str(algo).split('.')[1].upper():
				# print("skipping : ")
				# print(algo)
				index = index+1
				continue
			value = float(entry[index])
			pred= -1
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
			index = index+1
		
		if entry[index] == 0 or entry[index] ==1:
			y_actual.append(0)
		else:
			y_actual.append(1)

	cm = metrics.confusion_matrix(y_actual, y_pred)
	# print(cm)
	precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred, average=None)
	
	return precision[1],recall[1], f1[1]

def getF1_SAF_allrows(allEntries):

	y_pred = {}

	for algo in ALGOS:
		algoStr = str(algo).split('.')[1].upper()
		y_pred[algoStr] = []
		y_pred[algoStr].append([])
		y_pred[algoStr].append([])

	y_actual = []

	print(len(allEntries))

	threshold_sets = {'St_c_DS':getThreshold(THRESHOLD_SETS.HUMANCLONE_QUART3, DB_SETS.GT10_DB_DATA, 'all'), 
						'St_n_DS': getThreshold(THRESHOLD_SETS.HUMANND_MEDIAN, DB_SETS.GT10_DB_DATA, 'all'), 
						'O_s_DS' : getThreshold(THRESHOLD_SETS.OPTIMAL, DB_SETS.GT10_DB_DATA, 'all'),
						'O_c_DS' : getThreshold(THRESHOLD_SETS.OPTIMAL_CLASSIFICATION_CLONE, DB_SETS.GT10_DB_DATA, 'all'),
						'O_n_DS' : getThreshold(THRESHOLD_SETS.OPTIMAL_CLASSIFICATION_ND, DB_SETS.GT10_DB_DATA, 'all')}
	
	# threshold_sets["proportion_based"] = [getThreshold(THRESHOLD_SETS.FULLDB_QUART1, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]
	# threshold_sets["sample_based"] = [getThreshold(THRESHOLD_SETS.HUMANCLONE_QUART3, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.HUMANND_MEDIAN.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]
	

	algoScoreRows = []
	fieldNames = ['thresholdSet', 'algoName', 'threshold', 'precision', 'recall', 'f1']

	dummyClassifier = dummy.DummyClassifier(strategy="stratified")

	print(threshold_sets)

	for threshold_set_name in threshold_sets :
		threshold_set= threshold_sets[threshold_set_name]
		for algo in ALGOS:
			algoStr = str(algo).split('.')[1].upper()
			threshold = threshold_set[algoStr]
			precision, recall, f1 = getF1_SAF(threshold, allEntries, algoStr)
			algoScoreRows.append({'thresholdSet':threshold_set_name, 'algoName':algoStr, 'threshold':threshold, 'precision':precision, 'recall':recall, 'f1':f1})

	writeCSV(fieldNames, algoScoreRows, "rq2_1_" +str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".csv")

	
def getNdCategories(allEntries):
	print(allEntries[0][16])
	total = 0
	nd2count =0
	nd3count = 0
	for entry in allEntries:
		classification = entry[14]
		tags = entry[15]

		if classification ==1:
			total +=1
			if "dditional" in tags:
				nd3count +=1 
			else:
				nd2count += 1
	print(total)
	print(nd2count)
	print(nd3count)


if __name__=="__main__":
	dbName="/comparator/src/main/resources/GoldStandards/SS.db"
	connectToDB(dbName)
	allEntries = fetchAllNearDuplicates("where human_classification>=0")
	closeDBConnection()
	# testDummyClassifier()
	# getNdCategories(allEntries)

	# dbName = "/Test/gt10/gt10_last500Responses.db"
	# connectToDB(dbName)
	# allEntries = fetchAllNearDuplicates("where human_classification>=0")
	# closeDBConnection()
	
	# getF1_Classifier(allEntries)
	getF1_SAF_allrows(allEntries)

	# y_pred = {}

	# for algo in ALGOS:
	# 	algoStr = str(algo).split('.')[1].upper()
	# 	y_pred[algoStr] = []

	# y_actual = []

	# print(len(allEntries))

	# threshold_sets = {}
	# threshold_sets["proportion_based"] = [getThreshold(THRESHOLD_SETS.FULLDB_QUART1, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]
	# threshold_sets["sample_based"] = [getThreshold(THRESHOLD_SETS.HUMANCLONE_QUART3, DB_SETS.GT10_DB_DATA, 'all'), getThreshold(THRESHOLD_SETS.HUMANND_MEDIAN.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all'),  getThreshold(THRESHOLD_SETS.HUMANDIFF_MIN.FULLDB_MEDIAN, DB_SETS.GT10_DB_DATA, 'all')]

	# algoScoreRows = []
	# fieldNames = ['thresholdSet', 'algoName', 'c-thre', 'n-thre', 'precision', 'recall', 'f1']

	# dummyClassifier = sklearn.dummy.DummyClassifier(strategy="stratified")

	# print(threshold_sets)
	# for threshold_set_name in threshold_sets:
	# 	threshold_set = threshold_sets[threshold_set_name]
	# 	cloneThresholds = threshold_set[0]
	# 	ndThresholds = threshold_set[1]
	# 	# print(cloneThresholds)
	# 	for entry in allEntries:
	# 		index=4
	# 		for algo in ALGOS:
	# 			algoStr = str(algo).split('.')[1].upper()
	# 			value = float(entry[index])
	# 			pred = -1
	# 			if algo.value[2] == "lt":
	# 				if value <= cloneThresholds[algoStr]:
	# 					pred = 0
	# 				if value > cloneThresholds[algoStr]:
	# 					if value <= ndThresholds[algoStr]:
	# 						pred = 1
	# 					else:
	# 						pred = 2
	# 			else:
	# 				if value >= cloneThresholds[algoStr]:
	# 					pred = 0
	# 				if value < cloneThresholds[algoStr]:
	# 					if value >= ndThresholds[algoStr]:
	# 						pred = 1
	# 					else:
	# 						pred = 2

	# 			y_pred[algoStr].append(pred)
	# 			index = index+1

	# 		y_actual.append(entry[index])


	# 	for algo in ALGOS:
	# 		algoStr = str(algo).split('.')[1].upper()
	# 		cm = metrics.confusion_matrix(y_actual, y_pred[algoStr])
	# 		# print(cm)
	# 		precision, recall, f1, support = metrics.precision_recall_fscore_support(y_actual, y_pred[algoStr], average="macro")
	# 		# precision = metrics.precision(y_actual, y_pred[algoStr], average="macro")
	# 		# recall = metrics.recall(y_actual, y_pred[algoStr], average="macro")
	# 		# f1 = metrics.f1_score(y_actual, y_pred[algoStr], average="macro")
	# 		row = {'thresholdSet' : threshold_set_name, 'algoName':algoStr, 'c-thre':cloneThresholds[algoStr],'n-thre':ndThresholds[algoStr],'precision':precision, 'recall':recall, 'f1':f1}
	# 		algoScoreRows.append(row)


	# writeCSV(fieldNames, algoScoreRows, "rq1.csv")