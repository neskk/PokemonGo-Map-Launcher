import os
import glob
import sys

if len(sys.argv) < 2:
    print('Script usage: python remove_banned.py <banned-accounts-filename>')
    exit()

banned_filename = sys.argv[1]
with open(banned_filename, 'r+') as f:
    banned_usernames = set(f.read().splitlines())

    if not banned_usernames:
        print('Zero accounts are banned, our job is done.')
        exit()

path = os.path.dirname(os.path.realpath(__file__))
filenames = {}
counts = {}
content = []

file_id = 0
for filename in glob.glob(os.path.join(path, '*.csv')):
    print('Reading file: ' + filename)
    total = 0
    banned = 0
    accounts = []
    banned_accounts = []
    with open(filename, 'r+') as f:
        for line in f:
            fields = line.split(',')
            if len(fields) < 3:
                continue
            total += 1
            username = fields[1]
            if username in banned_usernames:
                banned += 1
                banned_accounts.append(line)
                continue
            accounts.append(line)
        print('Read {} accounts and found {} banned.'.format(total, banned))
        f.close()

    with open(filename, 'w+') as f:
        f.seek(0)
        for account in accounts:
            f.write('%s' % account)
        f.truncate()
        f.close()

    with open('removed.txt', 'a') as f:
        for account in banned_accounts:
            f.write('%s' % account)
        f.truncate()
        f.close()

print('Banned accounts cleanup complete!')
