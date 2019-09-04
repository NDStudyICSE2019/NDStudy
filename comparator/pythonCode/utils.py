import json

def importJson(jsonFile):
	try:
		with open(jsonFile, encoding='utf-8') as data_file:
			data = json.loads(data_file.read())
			return data
	except Exception as ex:
		print("Exception occured while importing json from : " + jsonFile)
		print(ex)
		return None

def exportJson(jsonData, file):
	with open(file, "w") as write_file:
		json.dump(jsonData, write_file)