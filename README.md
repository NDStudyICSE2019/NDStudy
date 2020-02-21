# NDStudy

Virtual Machine set up with the tools

    Download from : https://zenodo.org/record/3387759
    password for VM : ICSE2019
    

Tools Provided

    Comparator
    Crawljax

DataSets Provided

    Download from https://doi.org/10.5281/zenodo.3376730
    DS.db (contains 493K state-pairs and 1000 labelled pairs) 
    SS.db (contains 97.5K labelled state-pairs for 9 subjects)
    TS.db (same as DS but 500 labelled pairs (disjoint from 1000 in DS) to validate
    More info on usage [here](./DataSetUsage.md) 
    
Complete set of Crawl Models 
    
    Download from https://doi.org/10.5281/zenodo.3385377
    Crawls from web used to build DS.db (DS_Crawls)
    GroundTruths for Subjects (GroundTruthModels) - manually labelled
    Crawls used for RQ3 (RQ3Crawls) - manually labelled
    More info on models in [here](./GoldStandards/readme.md)
    
Sample Ground Truth Model, Crawl Models Provided for 

    Petclinic application
    
Subject Applications in WebApps Folder

    Addressbook
    Petclinic
    Claroline
    Dimeshift
    PageKit
    PPMA
    MRBS
    MantiBT
    Phoenix


    

Manual Setup for tools (Skip if using VM)

    Requirements:
        Docker (For subject apps)
        Java 8
        Opencv (lib for MacOSX provided)
        Python 3
 
     For RQ1 and 2, comparator project is required.
     The project has been tested on MacOSX (High Sierra and above), Red Hat Enterprise Linux 6 (RHEL 6) 

     To setup the comparator project :
  
    Please set appropriate location for OpenCV library in:
        src/main/java/runner/Main.java 

    For Mac OS:
      OpenCV dylib has been provided in in resources/lib/. 
    
    To build : mvn package -DskipTests

      For RQ3 you need Crawljax. Either get the latest release of crawljax(https://github.com/crawljax) directly or you can use the version provided here. But you have to replace crawljax/examples with the examples folder provided here to run the experiments.
  
    To setup crawljax project: 
    
         Please set appropriate location for OpenCV library in:
         /crawljax-core/src/main/java/com/crawljax/stateabstractions/visual/OpenCVLoad.java
    
    To build the project : 
        cd <crawljax_folder>
        mvn package -DskipTests
    

 
 
Getting Results  

    For getting distance between states in a crawl model (has to be produced by crawljax latest version):
 
        build the comparator project
        set the location of the crawl to use in  comparator/batchRun_GSComp.sh (variable crawledFolder on the first line of the script)
        run the shell script : ./comparator/batchRun_GSComp.sh

    
    To create a ground truth Model 
    
        set the location of crawl (on line 375, variable CRAWL_PATH) you want to use as ground truth in (htmlCreator.py) and run :
        python3 htmlCreator.py
    
        After labelling the pairs by open html file in crawl_folder/gs/gs.html, save the gsResults.json in the same folder.
    
        Now this ground truth can be used to analyze other crawls that are created for this subject
     
    
  
    Thresholds for each subject have been computed and saved in 
    
        threshold_data.py

    
    For running crawls :
    (Skip for VM)     set the location of crawljax/examples/target/example_xxx_jar_with_dependencies.jar in 
            comparator/pythonCode/runCrawljaxBatch.py (BASE_COMMAND) 
     
        python3 comparator/runCrawljaxBatch.py

            for 5 minute crawls: runAllAlgos()
            for 30 minute crawls: runBestCrawls()
     
     

     For RQ2 run : 
        * Requires Databases provided in (https://doi.org/10.5281/zenodo.3376730) 
        to be placed in (comparator/src/main/resources/GoldStandards/)
        The thresholds to be used are set in pythonCode/globalNames.py THRESHOLD_SETS)
        You will need to uncomment HUMAN_QUART3, OPTIMAL_CLASSIFICATION_CLONE and OPTIMAL_CLASSIFICATION_ND
        
        python3 comparator/pythonCode/RQ1.py
     
     For RQ3:
        python3 comparator/analyzeCrawl.py
        {
            for 5 minute crawls: analyzeAllCrawls()
            for 30 minute crawls: analyzeBestCrawls()
         }
         
         - Needs to be run twice to generate classification.html for a crawl to be analyzed.
         - save the classification json in "saveJsons" folder by opening classification.html.
         - run analyzeCrawl.py again to obtain the statistics for the crawl.
 
