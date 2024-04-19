import sys, getopt

from ActionRoute import ActionProcessor

def mainMethod(incomingData):
    performAction = ActionProcessor(incomingData)
    return performAction.process()

if __name__ == "__main__":
    
    options = ''
    try:
        opts, args = getopt.getopt(sys.argv[1:],"h:o:",["options="])
    except getopt.GetoptError:
        print 'python . -o <options>'
        sys.exit(2)

    for opt, arg in opts:
        if opt == '-h':
            print 'python . -o <options>'
            sys.exit()

        elif opt in ("-o", "--options"):
            options = arg
    print mainMethod(options)
