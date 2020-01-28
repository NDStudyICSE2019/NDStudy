from enum import Enum
import os

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

############### APPS #################
APPS = ['addressbook', 'petclinic', 'claroline', 'dimeshift', 'pagekit', 'phoenix', 'ppma', 'mrbs', 'mantisbt']
DOCKERIZED_APPS = ['dimeshift', 'pagekit', 'phoenix', 'claroline', 'addressbook', 'ppma', 'mrbs', 'mantisbt', 'collabtive']
ND3_APPS = ['addressbook', 'pagekit', 'phoenix']
DOCKER_LOCATION = os.path.abspath(os.path.join("..","..","webApps"))

def getDockerName(appName):
	if ((appName.strip().lower() == 'addressbook') or (appName.strip().lower() == 'claroline') ):
		return 'dbApps'

	return appName

def isDockerized(appName):
	if(appName.strip() in DOCKERIZED_APPS):
		return True
	return False

def isNd3App(appName):
	if(appName.strip() in ND3_APPS):
		return True
	return False

############### DB NAMES #################
DB_NAME = 'DS.db'
GS_DB_NAME = 'SS.db'

############## FOLDER AND FILE NAMES ################
SCREENSHOTS = 'screenshots'
DOMS = 'doms'
STATES = 'states'
COMP_OUTPUT='comp_output'
RESULTS_FOLDER = "results"
VERIFIED_CLASSIFICATION_JSON_NAME = 'classification_verified.json'
GENERATED_CLASSIFICATION_JSON_NAME = 'classification.json'
GS_JSON_NAME = 'gsResults.json'
RESULT_JSON = "result.json"
CONFIG_JSON = "config.json"
DISTANCES_RESPONSES_JSON = 'classification_with_distances.json'

NODESIZES_JSON = 'nodeSizes.json'
PIXELSIZES_JSON = 'pixelSizes.json'
DOMSIZES_JSON = 'domSizes.json'

def getResultsFolder():
	return os.path.join(os.path.abspath(".."), RESULTS_FOLDER)

def getPreDefinedSaveJsonLocation():
	return os.path.abspath("../saveJsons")

def buildCrawlFolderName(appName, algo, threshold, runtime=5):
	crawlFolderName = appName + "_" + algo + "_" + str(float(threshold))+ "_" + str(runtime) + "mins"
	return crawlFolderName


############## THRESHOLD SETS ################
class DB_SETS(Enum):
	GT10_DB_DATA = {'name':'gt10_db_data'}
	GS_DB_DATA = {'name':'gs_db_data'}

class FILTER(Enum):
	CLONES = {'name':'clones'}
	NEAR_DUPLICATES = {'name':'near_duplicates'}
	DIFFERENT = {'name':'different'}
	NOZEROES = {'name':'noZeroes'}
	NEAR_DUPLICATES_DYN = {'name':'near_duplicates_dyn'}
	NEAR_DUPLICATES_ADD = {'name':'near_duplicates_add'}
	ALL = {'name':'all'}
	OPTIMAL = {'name':'optimal'}
	OPTIMAL_CLASSIFICATION = {'name':'optimal_classification'}

class THRESHOLD_SETS(Enum):
	# FULLDB_QUART1 = 	{'name':'fullDB_quart1', 	 'percentile':25, 	'filter':FILTER.ALL, 				 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA] , 'appSpecific': True}
	# FULLDB_MEDIAN = 	{'name':'fullDB_median', 	 'percentile':50, 	'filter':FILTER.ALL, 				 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA] , 'appSpecific': True}
	# #EXCLUDE0_QUART1 = 	{'name':'exclude0_quart1', 	 'percentile':25, 	'filter':FILTER.NOZEROES,			 'dataSet':[DB_SETS.GT10_DB_DATA] , 'appSpecific': False}
	# HUMANCLONE_QUART3 = {'name':'humanClone_quart3', 'percentile':75, 	'filter':FILTER.CLONES, 			 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA] ,	'appSpecific': True}#, DB_SETS.GS_DB_DATA
	# HUMANNDDYN_QUART1 = {'name':'humanNDDyn_quart1', 'percentile':25, 	'filter':FILTER.NEAR_DUPLICATES_DYN, 'dataSet':[DB_SETS.GS_DB_DATA] ,	'appSpecific': True}
	HUMANNDDYN_MEDIAN = {'name':'humanNDDyn_median', 'percentile':50, 	'filter':FILTER.NEAR_DUPLICATES_DYN, 'dataSet':[DB_SETS.GS_DB_DATA] ,	'appSpecific': True}
	# # HUMANDIFF_MIN =		{'name':'humanDiff_min',	 'percentile':0, 	'filter':FILTER.DIFFERENT, 		  	 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA] , 'appSpecific': True}
	HUMANND_MEDIAN = 	{'name':'humanND_median',	 'percentile':50, 	'filter':FILTER.NEAR_DUPLICATES, 	 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA] , 'appSpecific': True}#, DB_SETS.GS_DB_DATA
	OPTIMAL = {'name':'optimal', 'percentile':0, 'filter':FILTER.OPTIMAL, 'dataSet':[DB_SETS.GT10_DB_DATA, DB_SETS.GS_DB_DATA], 'appSpecific':True}
	# OPTIMAL_CLASSIFICATION_CLONE = {'name':'optimal_classification_clone',	 'percentile':25, 	'filter':FILTER.OPTIMAL_CLASSIFICATION, 	 'dataSet':[DB_SETS.GT10_DB_DATA] , 'appSpecific': False}
	# OPTIMAL_CLASSIFICATION_ND = {'name':'optimal_classification_nd',	 'percentile':75, 	'filter':FILTER.OPTIMAL_CLASSIFICATION, 	 'dataSet':[DB_SETS.GT10_DB_DATA] , 'appSpecific': False}

############# MISCELLANEOUS ########################
UNALTERED_GS_TAG = "_unaltered"

def testEnum():
	for thresholdSet in THRESHOLD_SETS:
		print(thresholdSet.value['name'])

if __name__=="__main__":
	testEnum()