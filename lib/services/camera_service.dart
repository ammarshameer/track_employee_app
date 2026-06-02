import 'dart:convert';
import 'dart:io';
import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';

class CameraService {
  static List<CameraDescription>? _cameras;
  
  static Future<void> initializeCameras() async {
    try {
      _cameras = await availableCameras();
    } catch (e) {
      print('Error initializing cameras: $e');
    }
  }

  /// Select the front camera if available, otherwise the first one.
  static CameraDescription _preferFrontCamera(List<CameraDescription> cameras) {
    return cameras.firstWhere(
      (c) => c.lensDirection == CameraLensDirection.front,
      orElse: () => cameras.first,
    );
  }

  /// Read a file as a base64 data-URI and delete the temporary file.
  static Future<String> _fileToBase64DataUri(File imageFile) async {
    final bytes = await imageFile.readAsBytes();
    final base64 = base64Encode(bytes);
    await imageFile.delete();
    return 'data:image/jpeg;base64,$base64';
  }

  static Future<String?> captureImage() async {
    try {
      if (_cameras == null || _cameras!.isEmpty) {
        await initializeCameras();
      }

      if (_cameras == null || _cameras!.isEmpty) {
        throw Exception('No cameras available');
      }

      final camera = _preferFrontCamera(_cameras!);

      final controller = CameraController(
        camera,
        ResolutionPreset.medium,
        enableAudio: false,
      );

      await controller.initialize();

      final Directory tempDir = await getTemporaryDirectory();
      final String timestamp = DateTime.now().millisecondsSinceEpoch.toString();
      final String imagePath = '${tempDir.path}/login_$timestamp.jpg';

      final XFile image = await controller.takePicture();
      await image.saveTo(imagePath);
      await controller.dispose();

      return await _fileToBase64DataUri(File(imagePath));
    } catch (e) {
      print('Camera capture error: $e');
      return null;
    }
  }

  static Future<String?> captureImageWithUI(BuildContext context) async {
    try {
      if (_cameras == null || _cameras!.isEmpty) {
        await initializeCameras();
      }

      if (_cameras == null || _cameras!.isEmpty) {
        throw Exception('No cameras available');
      }

      return await Navigator.push<String>(
        context,
        MaterialPageRoute(
          builder: (context) => const CameraCaptureScreen(),
        ),
      );
    } catch (e) {
      print('Camera UI error: $e');
      return null;
    }
  }
}

class CameraCaptureScreen extends StatefulWidget {
  const CameraCaptureScreen({super.key});

  @override
  State<CameraCaptureScreen> createState() => _CameraCaptureScreenState();
}

class _CameraCaptureScreenState extends State<CameraCaptureScreen> {
  CameraController? _controller;
  bool _isInitialized = false;
  bool _isCapturing = false;

  @override
  void initState() {
    super.initState();
    _initializeCamera();
  }

  Future<void> _initializeCamera() async {
    try {
      final cameras = await availableCameras();
      if (cameras.isEmpty) return;

      final camera = CameraService._preferFrontCamera(cameras);

      _controller = CameraController(
        camera,
        ResolutionPreset.medium,
        enableAudio: false,
      );

      await _controller!.initialize();
      
      if (mounted) {
        setState(() {
          _isInitialized = true;
        });
      }
    } catch (e) {
      print('Camera initialization error: $e');
    }
  }

  Future<void> _captureImage() async {
    if (_controller == null || !_controller!.value.isInitialized) return;

    setState(() {
      _isCapturing = true;
    });

    try {
      final XFile image = await _controller!.takePicture();
      final dataUri = await CameraService._fileToBase64DataUri(File(image.path));

      if (mounted) {
        Navigator.pop(context, dataUri);
      }
    } catch (e) {
      print('Capture error: $e');
      setState(() {
        _isCapturing = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('Take Photo'),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
      ),
      body: _isInitialized
          ? Stack(
              children: [
                // Camera preview
                Positioned.fill(
                  child: CameraPreview(_controller!),
                ),
                
                // Capture button
                Positioned(
                  bottom: 50,
                  left: 0,
                  right: 0,
                  child: Center(
                    child: GestureDetector(
                      onTap: _isCapturing ? null : _captureImage,
                      child: Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: _isCapturing ? Colors.grey : Colors.white,
                          border: Border.all(
                            color: Colors.white,
                            width: 4,
                          ),
                        ),
                        child: _isCapturing
                            ? const Center(
                                child: CircularProgressIndicator(
                                  color: Colors.black,
                                ),
                              )
                            : const Icon(
                                Icons.camera_alt,
                                size: 40,
                                color: Colors.black,
                              ),
                      ),
                    ),
                  ),
                ),
              ],
            )
          : const Center(
              child: CircularProgressIndicator(
                color: Colors.white,
              ),
            ),
    );
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }
}
