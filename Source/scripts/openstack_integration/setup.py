__author__="039732"
__date__ ="$May 8, 2014 5:06:19 PM$"

from setuptools import setup,find_packages

setup (
  name = 'os-aps-integration',
  version = '0.1',
  packages = find_packages(),

  # Declare your packages' dependencies here, for eg:
  install_requires=['foo>=3'],

  # Fill in these to make your Egg ready for upload to
  # PyPI
  author = '039732',
  author_email = '',

  summary = 'Just another Python package for the cheese shop',
  url = '',
  license = '',
  long_description= 'Long description of the package',

  # could also include long_description, download_url, classifiers, etc.

  
)