#!/usr/bin/python

import datetime
import getpass
from optparse import OptionParser
import subprocess
import sys

import mutagen
from mutagen import easyid3

from gobble import get_parser, GobbleServer, GobbleTrack


def _parse_date(string):
    process = subprocess.Popen(['date', '-d %s' % (string,), '+%s'],
                               stdout=subprocess.PIPE)
    string = process.communicate()[0].strip()
    return datetime.datetime.utcfromtimestamp(float(string))


if __name__ == '__main__':
    usage = "%prog [--server <SERVER>] <USERNAME> <START TIME> <MEDIA FILES>"
    parser = get_parser(usage=usage)
    opts,args = parser.parse_args()
    if len(args) < 3:
        parser.error("All arguments are required.")

    username,start_string = args[:2]
    server = opts.server
    password = getpass.getpass()
    tracks = args[2:]
    server = GobbleServer(server, username, password)

    dt = _parse_date(start_string)
    input = ''
    while input not in ['y', 'n']:
        input = raw_input("Did you mean '%s UTC'? [Y/n]: " % (dt,)).lower()
    if input == 'n':
        sys.exit()

    for track in tracks:
        f = mutagen.File(track)
        if f is None:
            raise Exception("%s caused problems." % (track,))
        if isinstance(f, mutagen.mp3.MP3):
            f = mutagen.mp3.MP3(track, ID3=easyid3.EasyID3)
        title = f['title'][0]
        artist = f['artist'][0]
        length = f.info.length
        album = f['album'][0]
        tracknumber = f['tracknumber'][0]
        t = GobbleTrack(artist, title, dt, album=album, length=length,
                        tracknumber=tracknumber)
        server.add_track(t)
        dt += datetime.timedelta(seconds=length)
    server.submit()
