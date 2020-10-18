import ScreenCloud
import json
import traceback

from PythonQt.QtCore import QByteArray, QBuffer, QIODevice, QFile
from PythonQt.QtGui import QWidget, QDialog
from PythonQt.QtUiTools import QUiLoader


class XBackBoneUploader:

    def __init__(self):
        self.uil = QUiLoader()
        self.config_path = workingDir + '/config.json'
        self.loadSettings()

    def showSettingsUI(self, parentWidget):
        self.parentWidget = parentWidget
        self.settingsDialog = self.uil.load(QFile(workingDir + '/settings.ui'), parentWidget)

        self.settingsDialog.connect('accepted()', self.saveSettings)
        self.loadSettings()

        self.settingsDialog.group_url.token.text = self.token
        self.settingsDialog.group_url.host.text = self.host

        self.settingsDialog.open()

    def loadSettings(self):
        with open(self.config_path, 'r') as config:
            settings = json.load(config)

            self.token = settings.get('token')
            self.host = settings.get('host')

    def saveSettings(self):
        data = {
            'token': self.settingsDialog.group_url.token.text,
            'host': self.settingsDialog.group_url.host.text
        }
        with open(self.config_path, 'w') as config:
            json.dump(data, config)

    def isConfigured(self):
        self.loadSettings()
        return not (not self.token or not self.host)

    def getFilename(self):
        return ScreenCloud.formatFilename('screenshot_%Y-%m-%d_%H-%M-%S')

    def upload(self, screenshot, name):
        self.loadSettings()

        q_ba = QByteArray()
        q_buff = QBuffer(q_ba)

        q_buff.open(QIODevice.WriteOnly)
        screenshot.save(q_buff, ScreenCloud.getScreenshotFormat())
        q_buff.close()

        address = (self.host + '/upload').replace('//', '/')

        try:
            r = requests.post(address, files={'upload': q_ba.data()}, data={'token': self.token})
            data = r.json()
            url = data.get('url')

            if not url:
                raise Exception(data.get('message'))

            ScreenCloud.setUrl(url)

        except urllib.error.HTTPError as e:
            ScreenCloud.setError('Error while connecting to: ' + self.host + '\nError:\n' + e.fp.read())
            return False

        except Exception as e:
            try:
                ScreenCloud.setError('Could not upload to: ' + self.host + '\nError: ' + e.message)
            except AttributeError:
                ScreenCloud.setError('Unexpected error while uploading:\n' + traceback.format_exc())
            return False

        return True
