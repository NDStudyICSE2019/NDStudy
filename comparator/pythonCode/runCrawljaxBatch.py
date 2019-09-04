import os
from time import sleep
import psutil
from subprocess import check_call, CalledProcessError, Popen
from pathlib import Path
import shutil
from datetime import datetime
from decimal import Decimal
from pythonDBCreator import ALGOS
from threshold_data import STATISTICS
from globalNames import FILTER, THRESHOLD_SETS, DB_SETS, APPS, isDockerized, DOCKER_LOCATION, isNd3App
import glob


###########################################################################
## Threshold Utils ############
###########################################################################
def normalize(input):
	output = {}
	for key in input:
		output[key.upper()] = input[key]

	return output

def buildThreshold(data, thresholdSet):
	data = normalize(data)
	threshold = {}

	ltIndex = int(thresholdSet['percentile']/25)
	gtIndex = int(4 - ltIndex)

	for algo in ALGOS:
		algostr = str(algo).split('.')[1].upper()
		op = algo.value[2]
		if op=='lt' :
			threshold[algostr] = data[algostr][ltIndex]
		elif op=='gt': 
			threshold[algostr] = data[algostr][gtIndex]

	return threshold

def getThreshold(thresholdSetItem, data, app):
	threshold = None
	thresholdSet = thresholdSetItem.value
	dataName = []
	
	dataName.append(app)
	
	Filter = thresholdSet['filter'].value
	dataName.append(Filter['name']) 

	print(thresholdSet)

	for dataSetItem in thresholdSet['dataSet']:
		if dataSetItem!=data:
			continue

		dataSet = dataSetItem.value['name']
		print(dataSet)
		stat_data = STATISTICS[dataSet] 
		# print(stat_data)
		dataNameStr = "_".join(dataName)
		print(dataNameStr)
		if dataNameStr in stat_data:
			threshold = buildThreshold(stat_data[dataNameStr], thresholdSet)
		
	return threshold

def getBestThresholds(app):
	thresholds = {}
	thresholdSet = None
	if (isNd3App(app)):
		thresholdSet = THRESHOLD_SETS.HUMANNDDYN_MEDIAN.value
	else:
		thresholdSet = THRESHOLD_SETS.OPTIMAL.value

	dataName = []
	print(thresholdSet)
	if thresholdSet['appSpecific'] and app != None:
		dataName.append(app)
	else:
		print("DataName cannot be built for {0} and {1}".format(app, thresholdSet['name']))
		return

	Filter = thresholdSet['filter'].value
	dataName.append(Filter['name'])
	dataSet =  DB_SETS.GS_DB_DATA.value['name']
	print(dataSet)
	stat_data = STATISTICS[dataSet] 

	dataNameStr = "_".join(dataName)
	if dataNameStr in stat_data:
		thresholds[(dataSet, dataNameStr, thresholdSet['name'])] = buildThreshold(stat_data[dataNameStr], thresholdSet)

	thresholdPerSAF = {}
	for algo in ALGOS:
		algoStr = str(algo).split('.')[1].upper()
		thresholdList = []
		for thresholdSet in thresholds:
			if thresholds[thresholdSet] == None:
				continue
			threshold = thresholds[thresholdSet][algoStr]
			if threshold not in thresholdList:
				thresholdList.append(threshold)
		
		thresholdPerSAF[algoStr] = thresholdList

	
	return thresholds, thresholdPerSAF

def getAllThresholds(app = None):

	thresholds = {}
	
	for thresholdSetItem in THRESHOLD_SETS:
		thresholdSet = thresholdSetItem.value
		dataName = []
		if thresholdSet['appSpecific'] and app != None:
			dataName.append(app)
		else:
			dataName.append("all")

		Filter = thresholdSet['filter'].value
		dataName.append(Filter['name']) 

		for dataSetItem in thresholdSet['dataSet']:
			dataSet = dataSetItem.value['name']
			print(dataSet)
			stat_data = STATISTICS[dataSet] 

			dataNameStr = "_".join(dataName)
			if dataNameStr in stat_data:
				thresholds[(dataSet, dataNameStr, thresholdSet['name'])] = buildThreshold(stat_data[dataNameStr], thresholdSet)
			
			if app!=None and dataName[0] == app:
				dataNameStr = "all_" + "_".join(dataName[1:])
				print(dataNameStr)
				if dataNameStr in stat_data:
					thresholds[(dataSet, dataNameStr, thresholdSet['name'])] = buildThreshold(stat_data[dataNameStr], thresholdSet)
			

	thresholdPerSAF = {}
	for algo in ALGOS:
		algoStr = str(algo).split('.')[1].upper()
		thresholdList = []
		for thresholdSet in thresholds:
			if thresholds[thresholdSet] == None:
				continue
			threshold = thresholds[thresholdSet][algoStr]
			if threshold not in thresholdList:
				thresholdList.append(threshold)
		
		thresholdPerSAF[algoStr] = thresholdList

	
	return thresholds, thresholdPerSAF


###########################################################################
## CommandLine Utils ############
###########################################################################

def restartDocker(dockerName):

	stopDocker = ['docker', 'stop', dockerName]

	try:
		check_call(stopDocker)
	except CalledProcessError as ex:
		print("Could not stop docker docker? ")
		print(ex)

	removeDocker = ['docker', 'rm', dockerName]

	try:
		check_call(removeDocker)
	except CalledProcessError as ex:
		print("Could not remove docker? ")
		print(ex)

	# startDocker = ['/DIG_FSE2019/fse2019/'+ dockerName +'/run-docker.sh']
	startDocker = [os.path.join(DOCKER_LOCATION,  dockerName ,'run-docker.sh')]
	try:
		check_call(startDocker)
		sleep(30)
	except CalledProcessError as ex:
		print("No matching processes Found for docker? ")
		print(ex)



def cleanup(dockerName = None):
	killChromeDriverCommand = ['killall', 'chromedriver']
	try:
		check_call(killChromeDriverCommand)
	except CalledProcessError as ex:
		print("No matching processes Found for chromedriver? ")
		print(ex)

	killGoogleChromeCommand = ['killall', 'Google Chrome']

	try:
		check_call(killGoogleChromeCommand)
	except CalledProcessError as ex:
		print("No matching processes Found for Google Chrome? ")
		print(ex)

	if not dockerName is None:

		stopDocker = ['docker', 'stop', dockerName]

		try:
			check_call(stopDocker)
		except CalledProcessError as ex:
			print("Could not stop docker docker? ")
			print(ex)

		removeDocker = ['docker', 'rm', dockerName]

		try:
			check_call(removeDocker)
		except CalledProcessError as ex:
			print("Could not remove docker? ")
			print(ex)


def kill_process(pid):
	# try:
	# 	pid = int(pidfile_path.read_text())
	# except FileNotFoundError:
	# 	print("No {0}".format(pidfile_path))
	# 	return 0
	# except ValueError:
	# 	print("Invalid path : {0}".format(pidfile_path))
	# 	return 0
	try:
		proc = psutil.Process(pid)
		print("Killing", proc.name())
		proc.kill()
	except psutil.NoSuchProcess as ex:
		print("No Such Process : {0}".format(pid))

def monitorProcess(proc, runtime, timeStep=30, timeout=200,crawljaxOutputPath = None, existing = -1):
	done = False
	timeDone = 0
	graceTime = 60
	status = None
	while not done:
		poll = proc.poll()
		if poll == None:
			print("process still running")
			sleep(timeStep)
			timeDone += timeStep
		else:
			done = True
			status = STATUS_SUCCESSFUL
			break
		
		if timeDone >= (runtime*60 + graceTime):
			print("Process still running after allocated runtime. So waiting for timeout now!! ")
			
			cleanup()
			timeout_done = 0
			while timeout_done <= timeout:
				if crawljaxOutputPath!=None and existing >=0:
					if os.path.exists(crawljaxOutputPath):
						currentValidCrawls = glob.glob(crawljaxOutputPath + "/crawl*/result.json")
						current = len(currentValidCrawls)
						if current > existing:
							print("Output Done. So terminating!!")
							kill_process(proc.pid)
							done=True
							status = STATUS_STRAY_TERMINATED
							break

				sleep(timeStep)
				timeout_done += timeStep

			if not done :
				print("Process didn't output resultJson even after timeout, So terminating")
				kill_process(proc.pid)
				done=True
				status=STATUS_NO_OUTPUT
				break


	return status

def changeDirectory(path):
	try:
		os.chdir(path)
		return True
	except OSError as ex:
		print("Could not change director")
		print(ex)
		return False


def startProcess(command, outputPath="output_crawljax_" + str(datetime.now().strftime("%Y%m%d-%H%M%S")) + ".log"):
	changed = False
	current = os.getcwd()
	try:
		changed = changeDirectory('..')
		with open(outputPath,'w') as outputFile:
			proc = Popen(command, stdout=outputFile)
			print("Started {0} with PID {1}".format(command, proc.pid))
			return proc
	except Exception as ex:
		print(ex)
		print("Exception try to run {0} : ".format(command))
	finally:
		if changed:
			changeDirectory(current)

def runBestCrawls():
	BASE_COMMAND=['java', '-jar', '/art-fork/crawljax/examples/target/crawljax-examples-3.7-SNAPSHOT-jar-with-dependencies.jar']
	RUNTIME = 30
	succesful = []
	unsuccesful = []
	skipped = []
	excludeApps=['dimeshift', 'mantisbt', 'ppma']
	bestAlgos = ['DOM_RTED', 'VISUAL_SSIM', 'VISUAL_BLOCKHASH', 'VISUAL_PDIFF']
	for app in APPS:
		if app in excludeApps:
			continue
		thresholds, thresholdPerSAF = getBestThresholds(app)

		for algo in ALGOS:
			algoStr = str(algo).split('.')[1].upper()

			if algoStr not in bestAlgos:
				continue

			# for thresholdSet in THRESHOLDS:
			safThresholds = thresholdPerSAF[algoStr]

			for threshold in safThresholds:
				# threshold = thresholdSet[algoStr]
				
				logFile = os.path.join( "logs", "crawljaxLog_" + app+"_"+algoStr+"_" + str(RUNTIME) +"_" +str(threshold)+"_" + str(datetime.now().strftime("%Y%m%d-%H%M%S")+ ".log"))

				done, command = runAlgo(app, algoStr, RUNTIME, threshold, logFile, maxStates=150)
				
				if done == STATUS_SUCCESSFUL or done==STATUS_STRAY_TERMINATED:
					succesful.append(command)
				if done == STATUS_SKIPPED:
					skipped.append(command)
				if done == STATUS_ERRORED or done == STATUS_NO_OUTPUT:
					unsuccesful.append(command)

				
	print("succesful : {0}".format(str(len(succesful))))
	print(succesful)
	print("skipped : {0}".format(str(len(skipped))))
	print(skipped)			
	print("unsuccesful  : {0}".format(str(len(unsuccesful))))
	print(unsuccesful)
	if DRY_RUN:
		print("Predicted run time : " + str(RUNTIME*len(succesful)))


def runAllAlgos():
	# APPS = ['addressbook', 'petclinic', 'claroline', 'dimeshift', 'pagekit', 'phoenix']
	# APPS=['pagekit']
	RUNTIME = 5
	# MAX_STATES = 100
	
	succesful = []
	unsuccesful = []
	skipped = []

	excludeAlgos=[]
	# excludeAlgos = ['DOM_SIMHASH', 'DOM_CONTENTHASH','DOM_LEVENSHTEIN', 'DOM_RTED', 'VISUAL_PDIFF', 'VISUAL_SSIM', 'VISUAL_PHASH', 'VISUAL_HYST','VISUAL_BLOCKHASH']
	
	for app in APPS:
		#algo = ALGOS["VISUAL_SIFT"]
		#if algo != None:

		thresholds, thresholdPerSAF = getAllThresholds(app)

		for algo in ALGOS:
			algoStr = str(algo).split('.')[1].upper()

			if algoStr in excludeAlgos:
				continue

			# for thresholdSet in THRESHOLDS:
			safThresholds = thresholdPerSAF[algoStr]

			for threshold in safThresholds:
				# threshold = thresholdSet[algoStr]
				
				logFile = os.path.join( "logs", "crawljaxLog_" + app+"_"+algoStr+"_" + str(RUNTIME) +"_" +str(threshold)+"_" + str(datetime.now().strftime("%Y%m%d-%H%M%S")+ ".log"))

				done, command = runAlgo(app, algoStr, RUNTIME, threshold, logFile)
				
				if done == STATUS_SUCCESSFUL or done==STATUS_STRAY_TERMINATED:
					succesful.append(command)
				if done == STATUS_SKIPPED:
					skipped.append(command)
				if done == STATUS_ERRORED or done == STATUS_NO_OUTPUT:
					unsuccesful.append(command)

				
	print("succesful : {0}".format(str(len(succesful))))
	print(succesful)
	print("skipped : {0}".format(str(len(skipped))))
	print(skipped)			
	print("unsuccesful  : {0}".format(str(len(unsuccesful))))
	print(unsuccesful)
	if DRY_RUN:
		print("Predicted run time : " + str(RUNTIME*len(succesful)))


STATUS_SUCCESSFUL = "successful"
STATUS_NO_OUTPUT = "noOutput"
STATUS_STRAY_TERMINATED = "strayProcessTerminated_OutputObtained"
STATUS_SKIPPED = "skipped"
STATUS_ERRORED = "errored"

def runAlgo(appName, algo, runtime, threshold = -1, logFile = os.path.join( "logs", "crawljaxLog_" + str(datetime.now().strftime("%Y%m%d-%H%M%S"))+".log"), maxStates = -1, rerun=False):

	
	command = BASE_COMMAND.copy()
	command.append(appName)
	command.append(algo)
	command.append(str(runtime))


	if (threshold != -1):
		command.append(str(threshold))

	if(maxStates != -1):
		if (threshold == -1):
			command.append(str(threshold))
		command.append(str(maxStates))
	
	host = "localhost"
	if(isDockerized(appName)):
		host = "192.168.99.101"

	existingValidCrawls = []
	crawlFolderName = appName + "_" + algo + "_" + str(float(threshold))+ "_" + str(runtime) + "mins"
	crawljaxOutputPath = os.path.abspath(os.path.join("..", "out", appName, crawlFolderName, host))
	if os.path.exists(crawljaxOutputPath):
		existingValidCrawls = glob.glob(crawljaxOutputPath + "/crawl*/result.json")

	if (not rerun):
		if os.path.exists(crawljaxOutputPath):
			if len(existingValidCrawls) == 0:
				shutil.rmtree(crawljaxOutputPath)
			else:
				print("Ignoring run because a crawl already exists.")
				print("Call with rerun=True for creating a new crawl with the same configuration")
				status = STATUS_SKIPPED
				return status, command

	
	if DRY_RUN:
		status = STATUS_SUCCESSFUL
		return status, command


	if isDockerized(appName):
		restartDocker(appName)

	proc = startProcess(command, logFile)
	if proc==None:
		print("Ignoring error command.")
		status = STATUS_ERRORED
		return status, command
	
	timeout = 200
	if(algo == 'VISUAL_PDIFF'):
		timeout = 300

	status = monitorProcess(proc, runtime, timeout = timeout, crawljaxOutputPath = crawljaxOutputPath, existing = len(existingValidCrawls))
	print("Done : {0}".format(command))
	
	if isDockerized(appName):
		cleanup(appName)
	else:
		cleanup()
	return status, command




###########################################################################
##Tests ############
###########################################################################
def testCleanup():
	cleanup()
	print("cleanup tested")

def testGetThresholds():
	print(getAllThresholds('ppma'))

def testRestartDocker():
	restartDocker("dimeshift")

def testChangeDir():
	current = os.getcwd();
	print(os.getcwd())
	changeDirectory("..")
	print(os.getcwd())
	changeDirectory(current)
	print(os.getcwd())

def testGetBestThresholds():
	print(getBestThresholds('ppma'))

###########################################################################
## Main Code ############
###########################################################################

DRY_RUN = True

if __name__ == "__main__":

	BASE_COMMAND=['java', '-jar', '../crawljax/examples/target/crawljax-examples-3.7-SNAPSHOT-jar-with-dependencies.jar']

	# runBestCrawls()
	# runAllAlgos()
	
	