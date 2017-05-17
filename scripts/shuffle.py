import os
import glob
import random

path = os.path.dirname(os.path.realpath(__file__))
filenames = {}
counts = {}
content = []

file_id = 0
for filename in glob.glob(os.path.join(path, '*.csv')):
    print('Reading file: ' + filename)

    with open(filename, 'r+') as f:
        accounts = f.read().splitlines()
        filenames[file_id] = filename
        counts[file_id] = len(accounts)

        content.extend(accounts)
        f.close()
    file_id += 1

# Shuffle accounts
random.shuffle(content)

line = 0
for i in range(file_id):
    # os.remove(filename)
    count = counts[i]
    with open(filenames[i], 'w+') as f:
        f.seek(0)
        for j in range(count):
            f.write('%s\n' % content[line])
            line += 1
        f.truncate()

print('Account shuffling complete')
